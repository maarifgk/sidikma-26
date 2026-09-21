<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceSchools\InvoiceSchoolResource;
use App\Filament\Resources\InvoiceSchools\Pages\ListInvoiceSchools;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceSchoolResourceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Employee $employee;

    private PaymentInvoice $paidInvoice;

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
        $this->employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'name' => 'Andar Styawan, M.Pd.',
            'is_active' => true,
        ]);
        $this->paidInvoice = PaymentInvoice::query()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'employee_id' => $this->employee->getKey(),
            'academic_year' => '2026/2027',
            'invoice_number' => 'INV-2026-0001',
            'description' => 'Iuran Guru Agustus',
            'amount' => 20000,
            'status' => PaymentInvoice::STATUS_PAID,
            'due_date' => '2026-08-15',
            'paid_at' => '2026-08-10 08:00:00',
        ]);
        PaymentInvoice::query()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'academic_year' => '2026/2027',
            'invoice_number' => 'INV-2026-0002',
            'description' => 'Tagihan Kelas Agustus',
            'amount' => 81000,
            'status' => PaymentInvoice::STATUS_UNPAID,
            'due_date' => '2026-08-20',
        ]);
    }

    public function test_invoice_menu_displays_filters_summary_and_school_actions(): void
    {
        $this->get(InvoiceSchoolResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee("Invoice Pembayaran LP. Ma'arif NU PCNU Gunungkidul")
            ->assertSee('Filter Tahun Ajaran')
            ->assertSee('Pembayaran Lunas')
            ->assertSee('Belum Lunas')
            ->assertSee('Total Guru &amp; Pegawai', false)
            ->assertSee('Asal Sekolah/Madrasah')
            ->assertSee('Data Madrasah/Sekolah')
            ->assertSee('MI YAPPI Baleharjo')
            ->assertSee('Tingkat MI')
            ->assertSee('Detail')
            ->assertSee('Pembayaran Kelas');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Invoice');
    }

    public function test_invoice_table_can_be_searched_and_filtered_by_academic_year(): void
    {
        Livewire::test(ListInvoiceSchools::class)
            ->assertCanSeeTableRecords([$this->school])
            ->searchTable('Baleharjo')
            ->assertCanSeeTableRecords([$this->school])
            ->set('yearFilter', '2026/2027')
            ->call('applyAcademicYear')
            ->assertSet('academicYear', '2026/2027')
            ->assertSee('2026/2027')
            ->assertSee('Pembayaran Lunas')
            ->assertSee('Belum Lunas');
    }

    public function test_detail_and_class_invoice_pages_use_real_invoice_data(): void
    {
        $url = InvoiceSchoolResource::getUrl(
            'view',
            [
                'record' => $this->school,
                'year' => '2026/2027',
                'mode' => 'detail',
            ],
            panel: 'admin',
            isAbsolute: false,
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Detail Invoice')
            ->assertSee('MI YAPPI Baleharjo')
            ->assertSee('INV-2026-0001')
            ->assertSee('Andar Styawan, M.Pd.')
            ->assertSee('Iuran Guru Agustus')
            ->assertSee('Rp 20.000')
            ->assertSee('Lunas')
            ->assertSee('INV-2026-0002')
            ->assertSee('Tagihan Kelas Agustus')
            ->assertSee('Belum Lunas');

        $this->assertDatabaseHas('payment_invoices', [
            'id' => $this->paidInvoice->getKey(),
            'status' => PaymentInvoice::STATUS_PAID,
        ]);
    }
}
