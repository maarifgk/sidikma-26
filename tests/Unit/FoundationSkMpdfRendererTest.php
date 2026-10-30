<?php

namespace Tests\Unit;

use App\Models\FoundationSkTemplate;
use App\Services\FoundationSkMpdfRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FoundationSkMpdfRendererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sidikma-mpdf-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_renders_multipage_a4_pdf_from_html(): void
    {
        $template = new FoundationSkTemplate(['paper_size' => 'A4', 'orientation' => 'portrait']);
        $logo = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $html = '<!doctype html><html><head><style>@page{size:A4 portrait;margin:0}body{font-family:DejaVu Serif}.signature{page-break-inside:avoid}</style></head><body><img src="data:image/png;base64,'.$logo.'" alt="Logo dummy"><h1>Surat Keputusan Dummy</h1>'.str_repeat('<p>Isi keputusan untuk pengujian layout dokumen resmi.</p>', 120).'<div class="signature">Tanda Tangan Dummy</div></body></html>';

        $pdf = (new FoundationSkMpdfRenderer($this->tempDir))->render($html, $template);

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
        self::assertGreaterThan(1000, strlen($pdf));
        self::assertGreaterThanOrEqual(2, preg_match_all('/\/Type\s*\/Page\b/', $pdf, $matches));
    }

    public function test_renders_f4_pdf_with_explicit_page_format(): void
    {
        $template = new FoundationSkTemplate(['paper_size' => 'F4', 'orientation' => 'landscape']);

        $pdf = (new FoundationSkMpdfRenderer($this->tempDir))->render('<html><body><p>Dokumen F4 dummy</p></body></html>', $template);

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
    }

    public function test_rejects_remote_resources(): void
    {
        $this->expectException(RuntimeException::class);

        (new FoundationSkMpdfRenderer($this->tempDir))->render('<html><body><img src="https://example.test/logo.png"></body></html>', new FoundationSkTemplate);
    }
}
