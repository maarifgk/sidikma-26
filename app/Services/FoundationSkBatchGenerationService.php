<?php
namespace App\Services;

use App\Models\{FoundationSkDocument,FoundationSkNumberSetting,FoundationSkTemplate,User};
use App\Contracts\{FoundationSkPdfWriter,FoundationSkDocumentWriter};
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;

class FoundationSkBatchGenerationService
{
    public function __construct(private FoundationSkPdfWriter $pdfWriter, private FoundationSkDocumentWriter $documentWriter) {}
    public function generate(FoundationSkTemplate $template, iterable $users, string $periode, User $actor): array
    {
        abort_unless($actor->isAdminInduk() || $actor->can('sk-yayasan.manage'), 403);
        $users = array_values(is_array($users) ? $users : iterator_to_array($users));
        abort_if($users === [], 422, 'Minimal satu user harus dipilih.');
        foreach ($users as $user) {
            abort_unless($user instanceof User, 422, 'User tidak valid.');
            abort_unless($actor->isAdminInduk() || $actor->accessibleSchoolIds()->contains($user->employee?->school_id), 403);
        }

        $prepared = [];
        $createdFiles = [];
        $oldFiles = [];
        try {
            // Prepare phase: no counter or database document is touched here.
            foreach ($users as $user) {
                $prepared[] = ['user' => $user];
            }
            $result = DB::transaction(function () use ($prepared, $template, $periode, &$createdFiles, &$oldFiles): array {
                $setting = FoundationSkNumberSetting::query()->where('periode', $periode)->where('is_active', true)->lockForUpdate()->firstOrFail();
                $next = max((int) $setting->nomor_awal, (int) $setting->nomor_berikutnya);
                $documents = [];
                foreach ($prepared as $item) {
                    $raw = $next++;
                    $number = str_pad((string) $raw, (int) $setting->digit_nomor, '0', STR_PAD_LEFT);
                    $number = str_replace('{{nomor_urut}}', $number, (string) $setting->nomor_pattern);
                    $number = str_replace('{{nomor_urut_raw}}', (string) $raw, $number);
                    $number = str_replace('{{periode}}', $periode, $number);
                    $item['pdf'] = $this->pdfWriter->generateSingle($template, $item['user'], ['periode'=>$periode, 'nomor_sk'=>$number]);
                    $name = Str::slug($item['user']->name).'-'.date('Y').'-'.Str::random(16).'.pdf';
                    $path = "sk_yayasan/generated/{$periode}/{$name}";
                    if (! $this->documentWriter->storeFile($path, $item['pdf'])) throw new \RuntimeException('File PDF gagal disimpan.');
                    $createdFiles[] = $path;
                    $key = ['user_id'=>$item['user']->id,'tahun_sk'=>(int) preg_replace('/[^0-9].*/','',$periode),'sk_template_id'=>$template->id];
                    $doc = FoundationSkDocument::query()->where($key)->lockForUpdate()->first();
                    if ($doc?->file_path) $oldFiles[] = $doc->file_path;
                    $doc = $this->documentWriter->createOrUpdate($item['user'], $key['tahun_sk'], $template->id, $path, ['original_filename'=>'SK-'.$item['user']->name.'.pdf','stored_filename'=>$name,'mime_type'=>'application/pdf','file_size'=>strlen($item['pdf']),'source_type'=>'generated','matched_by'=>'generated'], auth()->user() ?? $item['user']);
                    $documents[] = $doc->fresh();
                }
                $setting->update(['nomor_berikutnya'=>$next]);
                return $documents;
            });
            foreach (array_unique($oldFiles) as $old) if ($old && ! in_array($old, $createdFiles, true)) $this->documentWriter->deleteFile($old);
            return $result;
        } catch (\Throwable $e) {
            $this->documentWriter->cleanupFiles($createdFiles);
            throw $e;
        } finally {
            // Prepared PDFs are memory values; this block is the cleanup boundary for future temp-file adapters.
            unset($prepared);
        }
    }
}
