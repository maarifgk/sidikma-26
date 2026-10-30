<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\FoundationSkTemplate;
use App\Models\School;
use App\Models\User;
use App\Services\FoundationSkPdfService;
use PHPUnit\Framework\TestCase;

class FoundationSkPdfServiceTest extends TestCase
{
    public function test_builder_rendering_resolves_supported_placeholders_and_layout_sections(): void
    {
        $template = new FoundationSkTemplate;
        $builder = FoundationSkTemplate::defaultBuilderData();
        $builder['header']['institution_name'] = 'Yayasan Contoh';
        $builder['decision']['title'] = 'Surat Keputusan';
        $builder['decision']['number'] = '{{nomor_sk}}';
        $builder['sections']['menimbang'] = 'Nama {{nama_lengkap}} beralamat {{alamat_html}}. Jabatan {{ketugasan}}.';
        $builder['signature']['nama'] = '{{nama_lengkap}}';
        $builder['copies']['title'] = 'Tembusan';
        $builder['copies']['items'] = 'Arsip {{nama_sekolah}}';
        $template->forceFill([
            'builder_data' => $builder,
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'custom_css' => '.custom { color: black; }',
        ]);

        $position = new EmployeePosition(['name' => 'Guru Kelas']);
        $assignment = new EmployeeAssignment;
        $assignment->setRawAttributes([
            'start_date' => '2024-01-01',
            'end_date' => '2026-12-31',
            'decree_period' => 'Januari',
        ]);
        $assignment->setRelation('position', $position);
        $employee = new Employee;
        $employee->setRawAttributes([
            'employee_code' => 'EMP-001',
            'nip' => 'NIP-001',
            'nuptk' => 'NUPTK-001',
            'birth_place' => 'Wonosari',
            'birth_date' => '1990-01-02',
            'address' => '<script>alert(1)</script> Jalan Contoh',
            'program_study' => 'Pendidikan',
            'employment_status' => 'GTY',
        ]);
        $employee->setRelation('school', new School(['name' => 'Sekolah Contoh']));
        $employee->setRelation('currentAssignment', $assignment);
        $user = new User(['name' => 'Budi Contoh', 'email' => 'budi@example.test']);
        $user->setRelation('employee', $employee);

        $html = (new FoundationSkPdfService)->renderHtml($template, $user, [
            'nomor_sk' => '0001/SK',
            'periode' => 'Januari',
            'tahun' => 2026,
        ]);

        self::assertStringContainsString('Budi Contoh', $html);
        self::assertStringContainsString('Sekolah Contoh', $html);
        self::assertStringContainsString('Guru Kelas', $html);
        self::assertStringContainsString('Tembusan', $html);
        self::assertStringContainsString('0001/SK', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('{{nama_lengkap}}', $html);
        self::assertStringContainsString('@page{size:A4 portrait', $html);
    }

    public function test_placeholder_catalog_is_the_source_used_by_the_editor(): void
    {
        $catalog = FoundationSkPdfService::placeholderDefinitions();

        self::assertArrayHasKey('ketugasan', $catalog);
        self::assertArrayHasKey('tanggal_sk', $catalog);
        self::assertArrayHasKey('logo_url', $catalog);
        self::assertSame('Nama lengkap user', $catalog['nama_lengkap']);
    }

    public function test_pdf_fallback_remains_a_valid_pdf_without_a_renderer_dependency(): void
    {
        $template = new FoundationSkTemplate;
        $template->forceFill([
            'builder_data' => FoundationSkTemplate::defaultBuilderData(),
            'paper_size' => 'A4',
            'orientation' => 'portrait',
        ]);

        $pdf = (new FoundationSkPdfService)->pdf($template);

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
    }
}
