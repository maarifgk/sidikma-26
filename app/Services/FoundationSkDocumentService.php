<?php

namespace App\Services;

use App\Models\FoundationSkDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class FoundationSkDocumentService
{
    public const DISK = 'documents';

    public function store(
        User $user,
        int $year,
        ?int $templateId,
        UploadedFile $file,
        User $actor,
        string $source = 'single',
        ?string $matchedBy = null,
        string $directory = 'sk_yayasan',
        bool $replaceExisting = false,
    ): FoundationSkDocument {
        $newPath = null;

        try {
            $document = DB::transaction(function () use (
                $user,
                $year,
                $templateId,
                $file,
                $actor,
                $source,
                $matchedBy,
                $directory,
                $replaceExisting,
                &$newPath,
            ): FoundationSkDocument {
                $existing = FoundationSkDocument::query()
                    ->where([
                        'user_id' => $user->id,
                        'tahun_sk' => $year,
                        'sk_template_id' => $templateId,
                    ])
                    ->lockForUpdate()
                    ->first();

                $this->guardExistingDocument($existing, $replaceExisting);

                $name = Str::slug($user->name).'-'.$year.'-'.Str::random(12).'.'.$file->extension();
                $directoryPath = "{$directory}/{$year}";
                $newPath = "{$directoryPath}/{$name}";

                $stored = Storage::disk(self::DISK)->putFileAs($directoryPath, $file, $name);

                if (! $stored) {
                    throw new \RuntimeException('Dokumen gagal disimpan.');
                }

                DB::connection()->afterRollBack(function () use ($newPath): void {
                    $this->deleteFileSafely($newPath);
                });

                $data = [
                    'user_id' => $user->id,
                    'sk_template_id' => $templateId,
                    'tahun_sk' => $year,
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_filename' => $name,
                    'file_path' => $newPath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'source_type' => $source,
                    'matched_by' => $matchedBy,
                    'uploaded_by' => $actor->id,
                ];

                if ($existing) {
                    // Keep the previous file as an archive; only metadata moves to the new file.
                    $existing->update($data);

                    return $existing->fresh();
                }

                return FoundationSkDocument::create($data);
            });
        } catch (Throwable $exception) {
            // A failed transaction must not leave an unreferenced new file.
            if ($newPath) {
                $this->deleteFileSafely($newPath);
            }

            throw $exception;
        }

        return $document;
    }

    private function guardExistingDocument(?FoundationSkDocument $document, bool $replaceExisting): void
    {
        if ($document && ! $replaceExisting) {
            throw new \RuntimeException(
                'Dokumen SK untuk guru dan tahun tersebut sudah tersedia. Konfirmasi penggantian terlebih dahulu.',
            );
        }
    }

    private function deleteFileSafely(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }

    public function normalize(string $value): string
    {
        $value = Str::ascii(pathinfo($value, PATHINFO_FILENAME));

        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }
}
