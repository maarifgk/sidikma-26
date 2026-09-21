<?php

namespace Tests\Feature;

use App\Filament\Resources\PaymentTypes\Pages\CreatePaymentType;
use App\Filament\Resources\PaymentTypes\Pages\EditPaymentType;
use App\Filament\Resources\PaymentTypes\Pages\ListPaymentTypes;
use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use App\Models\Foundation;
use App\Models\PaymentType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentTypeResourceTest extends TestCase
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

    public function test_payment_type_menu_matches_requested_table(): void
    {
        $this->get(PaymentTypeResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Pembayaran')
            ->assertSee('Add')
            ->assertSee('IURAN')
            ->assertSee('Pembayaran Batik')
            ->assertSee('OFF')
            ->assertSee('ON')
            ->assertSee('Dibuat')
            ->assertSee('Edit')
            ->assertSee('Delete');

        $batik = PaymentType::query()->where('name', 'Pembayaran Batik')->firstOrFail();

        Livewire::test(ListPaymentTypes::class)
            ->assertCanSeeTableRecords([$batik])
            ->assertTableActionVisible(EditAction::class, $batik)
            ->assertTableActionVisible(DeleteAction::class, $batik);
    }

    public function test_admin_can_add_edit_and_disable_payment_type(): void
    {
        PaymentType::ensureDefaults($this->foundation);

        $this->get(PaymentTypeResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tambah Pembayaran')
            ->assertSee('JENIS PEMBAYARAN')
            ->assertSee('Masukan Jenis Pembayaran')
            ->assertSee('STATUS')
            ->assertSee('--Pilih--')
            ->assertSee('Simpan')
            ->assertSee('Kembali');

        Livewire::test(CreatePaymentType::class)
            ->fillForm([
                'name' => 'Pembayaran Seragam',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $paymentType = PaymentType::query()->where('name', 'Pembayaran Seragam')->firstOrFail();

        Livewire::test(EditPaymentType::class, ['record' => $paymentType->getRouteKey()])
            ->fillForm([
                'name' => 'Pembayaran Seragam Baru',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('payment_types', [
            'id' => $paymentType->getKey(),
            'foundation_id' => $this->foundation->getKey(),
            'name' => 'Pembayaran Seragam Baru',
            'is_active' => false,
        ]);
        $this->assertArrayNotHasKey(
            'Pembayaran Seragam Baru',
            PaymentType::activeOptions($this->foundation->fresh()),
        );
    }

    public function test_payment_name_must_be_unique_for_application_foundation(): void
    {
        PaymentType::ensureDefaults($this->foundation);

        Livewire::test(CreatePaymentType::class)
            ->fillForm([
                'name' => 'Pembayaran Batik',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }
}
