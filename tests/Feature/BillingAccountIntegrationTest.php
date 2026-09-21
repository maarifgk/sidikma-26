<?php

namespace Tests\Feature;

use App\Filament\Resources\Billings\BillingResource;
use App\Filament\Resources\Billings\Pages\CreateBilling;
use App\Filament\Resources\Billings\Pages\ListBillings;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingAccountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $schoolAdmin;

    private User $teacher;

    private School $school;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);

        $foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
        $this->school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Dondong',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        $this->schoolAdmin = User::factory()->create([
            'name' => 'Admin MI YAPPI Dondong',
        ]);
        $this->schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $this->schoolAdmin->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'Andar Styawan, M.Pd.',
        ]);
        $this->teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        Membership::query()->create([
            'user_id' => $this->teacher->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        $this->employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'user_id' => $this->teacher->getKey(),
            'name' => $this->teacher->name,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_bill_linked_to_teacher_account_and_school(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        $this->get(BillingResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Pembayaran')
            ->assertSee('Export Excel')
            ->assertSee('Add')
            ->assertSee('User')
            ->assertSee('Asal Madrasah')
            ->assertSee('Tahun Ajaran')
            ->assertSee('Jenis Pembayaran')
            ->assertSee('Nilai')
            ->assertSee('Status')
            ->assertSee('Created');

        Livewire::test(CreateBilling::class)
            ->assertSet('step', 1)
            ->assertDontSee('INFO PENTING!!!!')
            ->set('academicYear', '2026/2027')
            ->set('schoolId', (string) $this->school->getKey())
            ->set('paymentType', 'Pembayaran Batik')
            ->call('searchTargets')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->assertDontSee('TAHUN AJARAN')
            ->assertSee('INFO PENTING!!!!')
            ->assertSee('Andar Styawan, M.Pd.')
            ->set('selectedUserIds', [$this->teacher->getKey()])
            ->set('amount', 643500)
            ->set('notes', 'Tagihan untuk guru.')
            ->call('addBills')
            ->assertHasNoErrors();

        $invoice = PaymentInvoice::query()->sole();

        $this->assertSame($this->teacher->getKey(), $invoice->user_id);
        $this->assertSame($this->employee->getKey(), $invoice->employee_id);
        $this->assertSame($this->school->getKey(), $invoice->school_id);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->teacher->getKey(),
        ]);

        Livewire::test(ListBillings::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_same_account_cannot_receive_duplicate_bill(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        $description = 'Pembayaran Batik';
        $this->createBill($this->teacher, $description);

        Livewire::test(CreateBilling::class)
            ->set('academicYear', '2026/2027')
            ->set('schoolId', (string) $this->school->getKey())
            ->set('paymentType', $description)
            ->call('searchTargets')
            ->assertHasNoErrors()
            ->assertSet('availableTargets', fn (array $targets): bool => collect($targets)
                ->doesntContain(fn (array $target): bool => $target['id'] === $this->teacher->getKey()));

        $this->expectException(QueryException::class);

        $this->createBill($this->teacher, $description);
    }

    public function test_teacher_only_sees_bills_addressed_to_own_account(): void
    {
        $ownBill = $this->createBill($this->teacher, 'Tagihan Guru');
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole(User::ROLE_GURU_PEGAWAI);
        Membership::query()->create([
            'user_id' => $otherTeacher->getKey(),
            'foundation_id' => $this->school->foundation_id,
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        $otherBill = $this->createBill($otherTeacher, 'Tagihan Guru Lain');

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);

        Livewire::test(ListBillings::class)
            ->assertCanSeeTableRecords([$ownBill])
            ->assertCanNotSeeTableRecords([$otherBill])
            ->assertDontSee('Edit')
            ->assertDontSee('Delete');

        $this->get(BillingResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tagihan Guru')
            ->assertDontSee('Tagihan Guru Lain');
    }

    public function test_school_admin_sees_all_bills_from_assigned_school_without_edit_tools(): void
    {
        $teacherBill = $this->createBill($this->teacher, 'Tagihan Guru Sekolah');
        $adminBill = $this->createBill($this->schoolAdmin, 'Tagihan Admin Sekolah');

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->schoolAdmin);

        Livewire::test(ListBillings::class)
            ->assertCanSeeTableRecords([$teacherBill, $adminBill])
            ->assertDontSee('Edit')
            ->assertDontSee('Delete');
    }

    private function createBill(User $target, string $description): PaymentInvoice
    {
        return PaymentInvoice::query()->create([
            'foundation_id' => $this->school->foundation_id,
            'school_id' => $this->school->getKey(),
            'user_id' => $target->getKey(),
            'employee_id' => $target->employee?->getKey(),
            'academic_year' => '2026/2027',
            'invoice_number' => PaymentInvoice::generateInvoiceNumber(),
            'description' => $description,
            'amount' => 50000,
            'status' => PaymentInvoice::STATUS_UNPAID,
        ]);
    }
}
