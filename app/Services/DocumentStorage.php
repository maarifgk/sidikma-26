<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\SchoolHead;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentStorage
{
    public const MAX_SIZE_KB = 1_024_000;

    /** @var array<int, string> */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /** @return array<string, string> */
    public static function documentTypeOptions(): array
    {
        return [
            'sk' => 'Surat Keputusan (SK)',
            'ijazah' => 'Ijazah',
            'sertifikat' => 'Sertifikat',
            'identitas' => 'Identitas',
            'legalitas' => 'Legalitas',
            'lainnya' => 'Dokumen Lainnya',
        ];
    }

    public static function directoryFor(Model $owner): string
    {
        $directory = match (true) {
            $owner instanceof Employee => 'employees',
            $owner instanceof Foundation => 'foundations',
            $owner instanceof School => 'schools',
            $owner instanceof SchoolHead => 'school-heads',
            default => null,
        };

        if ($directory === null || ! $owner->exists) {
            throw ValidationException::withMessages([
                'path' => 'Pemilik dokumen tidak valid.',
            ]);
        }

        return "{$directory}/{$owner->getKey()}";
    }

    /**
     * Complete metadata using the stored private file, never client metadata.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareCreateData(array $data, Model $owner, User $uploader): array
    {
        validator($data, [
            'document_type' => ['required', 'string', Rule::in(array_keys(self::documentTypeOptions()))],
            'path' => ['required', 'string'],
            'original_name' => ['required', 'string', 'max:255'],
        ])->validate();

        $path = $data['path'];
        $expectedDirectory = self::directoryFor($owner);

        if (! $this->isManagedPath($path, $expectedDirectory)) {
            throw ValidationException::withMessages([
                'path' => 'Lokasi file tidak sesuai dengan pemilik dokumen.',
            ]);
        }

        if (Document::withTrashed()->where('path', $path)->exists()) {
            throw ValidationException::withMessages([
                'path' => 'File tersebut sudah terdaftar.',
            ]);
        }

        $disk = Storage::disk(Document::PRIVATE_DISK);

        if (! $disk->exists($path)) {
            throw ValidationException::withMessages([
                'path' => 'File dokumen tidak ditemukan pada penyimpanan privat.',
            ]);
        }

        $size = $disk->size($path);
        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        if ($size > self::MAX_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'path' => 'Ukuran dokumen maksimal 1000 MB.',
            ]);
        }

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'path' => 'Format dokumen tidak diizinkan.',
            ]);
        }

        $originalName = basename(str_replace('\\', '/', $data['original_name']));
        $originalName = trim(str_replace(["\r", "\n", "\0"], '', $originalName));

        if ($originalName === '') {
            throw ValidationException::withMessages([
                'original_name' => 'Nama asli dokumen tidak valid.',
            ]);
        }

        return [
            'document_type' => $data['document_type'],
            'disk' => Document::PRIVATE_DISK,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'checksum' => hash('sha256', $disk->get($path)),
            'status' => Document::STATUS_ACTIVE,
            'uploaded_by' => $uploader->getKey(),
        ];
    }

    public function deleteUnreferenced(string $path, Model $owner): void
    {
        if (
            $this->isManagedPath($path, self::directoryFor($owner))
            && ! Document::withTrashed()->where('path', $path)->exists()
        ) {
            Storage::disk(Document::PRIVATE_DISK)->delete($path);
        }
    }

    private function isManagedPath(string $path, string $directory): bool
    {
        $prefix = "{$directory}/";
        $fileName = substr($path, strlen($prefix));

        return str_starts_with($path, $prefix)
            && filled($fileName)
            && ! str_contains($fileName, '/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0");
    }
}
