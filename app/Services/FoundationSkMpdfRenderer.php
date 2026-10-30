<?php

namespace App\Services;

use App\Models\FoundationSkTemplate;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use Throwable;

class FoundationSkMpdfRenderer
{
    public function __construct(private readonly ?string $tempDir = null) {}

    public function render(string $html, FoundationSkTemplate $template): string
    {
        $tempDir = $this->prepareTempDirectory();
        $html = $this->normalizePageCss($html);
        $this->assertNoRemoteResources($html);

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => $this->pageFormat($template),
                'orientation' => $template->orientation === 'landscape' ? 'L' : 'P',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'tempDir' => $tempDir,
                'default_font' => 'dejavuserif',
            ]);

            $mpdf->SetTitle('Surat Keputusan');
            $mpdf->WriteHTML($html);

            return $mpdf->Output('', Destination::STRING_RETURN);
        } catch (Throwable $exception) {
            throw new RuntimeException('Gagal merender Template SK menggunakan mPDF: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function pageFormat(FoundationSkTemplate $template): string|array
    {
        return match (strtoupper((string) $template->paper_size)) {
            'F4' => [215, 330],
            'LEGAL' => 'Legal',
            'LETTER' => 'Letter',
            default => 'A4',
        };
    }

    private function prepareTempDirectory(): string
    {
        $defaultDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sidikma-mpdf';

        if (function_exists('app') && app()->bound('path.storage')) {
            $defaultDirectory = storage_path('framework/cache/mpdf');
        }

        $directory = $this->tempDir ?: $defaultDirectory;

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori sementara mPDF tidak dapat dibuat.');
        }

        if (! is_writable($directory)) {
            throw new RuntimeException('Direktori sementara mPDF tidak dapat ditulis.');
        }

        return $directory;
    }

    private function assertNoRemoteResources(string $html): void
    {
        if (preg_match('/(?:\b(?:src|href)\s*=\s*["\']?|\burl\s*\(\s*["\']?)(?:https?:|data:text\/html|file:|javascript:|\/\/)/i', $html) === 1) {
            throw new RuntimeException('Resource remote atau tidak aman tidak diizinkan pada PDF Template SK.');
        }
    }

    private function normalizePageCss(string $html): string
    {
        return (string) preg_replace_callback('/@page\s*\{([^{}]*)\}/i', function (array $match): string {
            $rules = preg_replace('/\bsize\s*:[^;]+;?/i', '', $match[1]) ?? $match[1];

            return '@page{'.$rules.'}';
        }, $html);
    }
}
