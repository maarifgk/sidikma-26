<?php

namespace Tests\Feature\FoundationSk;

use App\Models\{Employee, FoundationSkNumberSetting, FoundationSkTemplate, User};
use App\Services\{FoundationSkNumberService, FoundationSkPdfService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationSkPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function template(array $overrides = []): FoundationSkTemplate
    {
        return FoundationSkTemplate::create(array_merge([
            'name' => 'Builder PDF', 'slug' => 'builder-pdf-'.uniqid(), 'paper_size' => 'A4', 'orientation' => 'portrait',
            'content' => 'Legacy {{nama_lengkap}}', 'html_template' => 'Legacy', 'custom_css' => '.old{}', 'is_active' => true,
            'builder_data' => ['header' => ['top_text' => 'KOP', 'institution_name' => 'Yayasan Maju', 'address' => 'Jl. Merdeka 1', 'whatsapp' => '0812', 'email' => 'info@y.test'], 'decision' => ['title' => 'SK Pengangkatan', 'number' => '{{nomor_sk}}', 'opening' => 'Dengan ini menetapkan {{periode}} {{tahun}} {{teks_nomor_sk}}'], 'sections' => ['menimbang' => 'Menimbang isi {{nis}} {{nip}} {{nuptk}}', 'mengingat' => 'Mengingat isi', 'memutuskan' => 'Memutuskan isi', 'menetapkan' => 'Menetapkan isi'], 'fonts' => ['body' => 11]],
        ], $overrides));
    }

    public function test_builder_data_and_runtime_placeholders_are_rendered(): void
    {
        $user = User::factory()->create(['name' => 'Budi Tester']);
        Employee::factory()->create(['user_id' => $user->id, 'name' => 'Budi Tester', 'nip' => 'NIP-2', 'nuptk' => 'NUPTK-3']);
        $user->setRelation('employee', Employee::query()->where('user_id', $user->id)->first());
        $html = app(FoundationSkPdfService::class)->renderHtml($this->template(), $user, ['periode' => '2026/2027', 'tahun' => 2026, 'nomor_sk' => '0099/SK', 'teks_nomor_sk' => 'SK Yayasan', 'jabatan' => 'Guru', 'nis' => 'NIS-1', 'nip' => 'NIP-2', 'nuptk' => 'NUPTK-3', 'nama_sekolah' => 'Sekolah Contoh']);

        foreach (['Yayasan Maju', 'Jl. Merdeka 1', 'SK Pengangkatan', 'Dengan ini menetapkan', 'Menimbang isi', 'Mengingat isi', 'Memutuskan isi', 'Menetapkan isi', 'Budi Tester', 'NIP-2', 'NUPTK-3', '2026/2027', '0099/SK'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringStartsWith('%PDF-', app(FoundationSkPdfService::class)->pdf($this->template(), $user, ['nomor_sk' => '0099/SK']));
    }

    public function test_missing_user_data_is_safe_and_legacy_template_still_renders(): void
    {
        $service = app(FoundationSkPdfService::class);
        $html = $service->renderHtml($this->template(), null);
        $this->assertStringContainsString('-', $html);
        $legacy = $this->template(['builder_data' => null, 'content' => '<p>{{nama_lengkap}} {{email}}</p>']);
        $this->assertStringContainsString('-', $service->renderHtml($legacy));
        $this->assertStringContainsString('%PDF-', $service->pdf($legacy));
    }

    public function test_paper_size_orientation_are_carried_to_rendered_document(): void
    {
        foreach ([['A4', 'portrait'], ['F4', 'landscape'], ['Legal', 'portrait']] as [$size, $orientation]) {
            $html = app(FoundationSkPdfService::class)->renderHtml($this->template(['paper_size' => $size, 'orientation' => $orientation]));
            $this->assertStringContainsString("@page{size:{$size} {$orientation}}", $html);
        }
    }

    public function test_generate_uses_number_service_and_advances_counter_after_success(): void
    {
        $template = $this->template(['builder_data' => null]);
        $user = User::factory()->create();
        FoundationSkNumberSetting::create(['periode' => '2026/2027', 'nomor_pattern' => 'SK/{{nomor_urut}}/{{tahun}}', 'nomor_awal' => 1, 'nomor_berikutnya' => 1, 'digit_nomor' => 4, 'is_active' => true]);
        $pdf = app(FoundationSkPdfService::class)->generateOne($template, $user, '2026/2027', app(FoundationSkNumberService::class));

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(2, FoundationSkNumberSetting::first()->nomor_berikutnya);
    }
}
