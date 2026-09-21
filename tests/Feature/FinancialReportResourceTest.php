<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialReports\FinancialReportResource;
use App\Filament\Resources\FinancialReports\Pages\ListFinancialReports;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialReportResourceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PaymentInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
        $this->school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'name' => 'Andar Styawan, M.Pd.',
            'is_active' => true,
        ]);
        $this->invoice = PaymentInvoice::query()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'employee_id' => $employee->getKey(),
            'academic_year' => '2026/2027',
            'invoice_number' => 'INV-LAP-0001',
            'description' => 'Iuran Guru',
            'amount' => 20000,
            'status' => PaymentInvoice::STATUS_PAID,
            'payment_method' => PaymentInvoice::METHOD_TRANSFER,
            'paid_at' => now(),
            'notes' => 'Pembayaran Agustus',
        ]);
    }

    public function test_financial_report_page_matches_requested_layout(): void
    {
        $this->get(FinancialReportResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Laporan Keuangan')
            ->assertSee('Tahun Ajaran')
            ->assertSee('Asal Madrasah')
            ->assertSee('Jenis Pembayaran')
            ->assertSee('Excel')
            ->assertSee('Andar Styawan, M.Pd.')
            ->assertSee('2026/2027')
            ->assertSee('Iuran Guru')
            ->assertSee('Rp 20.000')
            ->assertSee('Transfer Bank')
            ->assertSee('Lunas')
            ->assertSee('Pembayaran Agustus');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Laporan Keuangan');
    }

    public function test_financial_report_filters_use_invoice_data(): void
    {
        Livewire::test(ListFinancialReports::class)
            ->assertCanSeeTableRecords([$this->invoice])
            ->set('academicYear', '2026/2027')
            ->set('schoolId', (string) $this->school->getKey())
            ->set('paymentType', 'Iuran Guru')
            ->assertCanSeeTableRecords([$this->invoice])
            ->searchTable('Andar')
            ->assertCanSeeTableRecords([$this->invoice]);
    }

    public function test_financial_report_can_be_exported_for_excel(): void
    {
        Livewire::test(ListFinancialReports::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}
