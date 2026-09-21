<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\EditPaymentInfo;
use App\Filament\Admin\Pages\PaymentPage;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\PaymentFeeItem;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentPageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Employee $employee;

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
            'employee_code' => '3403032004.1165',
            'name' => 'Naily Yumna, S.Pd',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_payment_page_without_edit_tool(): void
    {
        $response = $this->get(PaymentPage::getUrl(panel: 'admin', isAbsolute: false));

        $response
            ->assertOk()
            ->assertSee('Pembayaran')
            ->assertSee('Asal Madrasah')
            ->assertSee('EWANUGK/Nama')
            ->assertSee('Cari')
            ->assertSee('Reset')
            ->assertSee('Informasi Pembayaran Iuran')
            ->assertSeeText("LP Ma'arif NU PCNU Gunungkidul")
            ->assertSee('Iuran Siswa Jenjang Madrasah Ibtidaiyah')
            ->assertSee('Penerbitan SK GTY/GTT/PTY/PTT Baru')
            ->assertSee('Rp 50.000');

        $this->assertStringNotContainsString('>Edit</button>', $response->getContent());
    }

    public function test_school_and_employee_filters_return_matching_payment_identity(): void
    {
        Livewire::test(PaymentPage::class)
            ->set('schoolId', $this->school->getKey())
            ->set('employeeId', $this->employee->getKey())
            ->call('searchPayments')
            ->assertHasNoErrors()
            ->assertSet('hasSearched', true)
            ->assertSee('Hasil Pencarian Pembayaran')
            ->assertSee('3403032004.1165')
            ->assertSee('Naily Yumna, S.Pd')
            ->assertSee('MI YAPPI Baleharjo')
            ->call('refreshPayments')
            ->assertSet('hasSearched', false)
            ->assertSet('schoolId', null)
            ->assertSet('employeeId', null);
    }

    public function test_admin_can_add_edit_and_remove_payment_information_columns(): void
    {
        $this->get(EditPaymentInfo::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Edit Informasi Pembayaran')
            ->assertSee('Tambah Kolom')
            ->assertSee('Hapus')
            ->assertSee('Simpan')
            ->assertSee('Kembali');

        $this->assertDatabaseCount('payment_fee_items', 8);

        Livewire::test(EditPaymentInfo::class)
            ->set('items.0.label', 'a. Iuran Siswa MI Terbaru')
            ->set('items.0.amount', 2500)
            ->call('removeItem', 1)
            ->call('addItem')
            ->set('items.7.section', PaymentFeeItem::SECTION_DECREE)
            ->set('items.7.label', 'c. Penerbitan Surat Keterangan Baru')
            ->set('items.7.amount', 30000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('payment_fee_items', 8);
        $this->assertDatabaseHas('payment_fee_items', [
            'label' => 'a. Iuran Siswa MI Terbaru',
            'amount' => 2500,
        ]);
        $this->assertDatabaseHas('payment_fee_items', [
            'section' => PaymentFeeItem::SECTION_DECREE,
            'label' => 'c. Penerbitan Surat Keterangan Baru',
            'amount' => 30000,
        ]);
        $this->assertDatabaseMissing('payment_fee_items', [
            'label' => 'b. Iuran Siswa Jenjang Madrasah Tsanawiyah/SMP',
        ]);

        $this->get(PaymentPage::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('a. Iuran Siswa MI Terbaru')
            ->assertSee('Rp 2.500')
            ->assertSee('c. Penerbitan Surat Keterangan Baru')
            ->assertDontSee('b. Iuran Siswa Jenjang Madrasah Tsanawiyah/SMP');
    }
}
