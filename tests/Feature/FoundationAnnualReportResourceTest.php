<?php

namespace Tests\Feature;

use App\Filament\Resources\FoundationAnnualReports\FoundationAnnualReportResource;
use App\Filament\Resources\FoundationAnnualReports\Pages\CreateFoundationAnnualReport;
use App\Filament\Resources\FoundationAnnualReports\Pages\EditFoundationAnnualReport;
use App\Models\Foundation;
use App\Models\FoundationAnnualReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FoundationAnnualReportResourceTest extends TestCase
{
    use RefreshDatabase;

    private Foundation $foundation;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $this->foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_annual_report_list_and_add_form(): void
    {
        $this->get(FoundationAnnualReportResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('LAPORAN TAHUNAN')
            ->assertSee('Tahun Anggaran')
            ->assertSee('Laporan Program Kerja')
            ->assertSee('Laporan Keuangan')
            ->assertSee('Add');

        $this->get(FoundationAnnualReportResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tambah Laporan Tahunan')
            ->assertSee('TAHUN ANGGARAN')
            ->assertSee('LAPORAN PROGRAM KERJA')
            ->assertSee('LAPORAN KEUANGAN')
            ->assertSee('Format PDF, maksimal 1000 MB.');
    }

    public function test_admin_can_create_and_edit_annual_report_with_private_pdf_files(): void
    {
        Storage::fake(FoundationAnnualReport::DISK);

        Livewire::test(CreateFoundationAnnualReport::class)
            ->fillForm([
                'budget_year' => '2026/2027',
                'work_program_report_path' => UploadedFile::fake()
                    ->create('laporan-program-kerja.pdf', 100, 'application/pdf'),
                'financial_report_path' => UploadedFile::fake()
                    ->create('laporan-keuangan.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $report = FoundationAnnualReport::query()->sole();

        $this->assertTrue($report->foundation->is($this->foundation));
        Storage::disk(FoundationAnnualReport::DISK)->assertExists($report->work_program_report_path);
        Storage::disk(FoundationAnnualReport::DISK)->assertExists($report->financial_report_path);

        Livewire::test(EditFoundationAnnualReport::class, ['record' => $report->getRouteKey()])
            ->fillForm(['budget_year' => '2027/2028'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2027/2028', $report->fresh()->budget_year);
    }

    public function test_deleting_report_cleans_its_files(): void
    {
        Storage::fake(FoundationAnnualReport::DISK);
        Storage::disk(FoundationAnnualReport::DISK)->put('work-program-reports/program.pdf', 'program');
        Storage::disk(FoundationAnnualReport::DISK)->put('financial-reports/finance.pdf', 'finance');

        $report = FoundationAnnualReport::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'budget_year' => '2026/2027',
            'work_program_report_path' => 'work-program-reports/program.pdf',
            'financial_report_path' => 'financial-reports/finance.pdf',
        ]);

        $report->delete();

        Storage::disk(FoundationAnnualReport::DISK)->assertMissing('work-program-reports/program.pdf');
        Storage::disk(FoundationAnnualReport::DISK)->assertMissing('financial-reports/finance.pdf');
    }
}
