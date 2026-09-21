<?php

namespace Tests\Feature;

use App\Filament\Resources\BatikOrders\BatikOrderResource;
use App\Filament\Resources\BatikOrders\Pages\CreateBatikOrder;
use App\Filament\Resources\BatikOrders\Pages\ListBatikOrders;
use App\Filament\Resources\BatikProducts\Pages\EditBatikProduct;
use App\Models\BatikOrder;
use App\Models\BatikProduct;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BatikCatalogResourceTest extends TestCase
{
    use RefreshDatabase;

    private Foundation $foundation;

    private School $school;

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
        $this->school = School::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }

    public function test_catalog_page_creates_and_displays_three_editable_product_templates(): void
    {
        $this->get(BatikOrderResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Pemesanan Kain Seragam Batik')
            ->assertSee('Batik Siswa MI')
            ->assertSee('Batik Siswa MTs/SMP')
            ->assertSee('Batik Guru')
            ->assertSee('Stok: 0')
            ->assertDontSee('Stok: 0,00')
            ->assertSee('Template Gambar Batik')
            ->assertSee('Edit')
            ->assertSee('Pesan Sekarang')
            ->assertSee('Asal Madrasah/Sekolah')
            ->assertSee('Total Pembayaran')
            ->assertSee('Penerima');

        $this->assertSame(3, BatikProduct::query()->count());
    }

    public function test_admin_can_replace_product_image_stock_price_and_size(): void
    {
        Storage::fake('public');
        $product = BatikProduct::ensureDefaults($this->foundation)->firstOrFail();

        Livewire::test(EditBatikProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'image_path' => UploadedFile::fake()->image('batik-mi.png', 1200, 700),
                'name' => 'Batik Siswa MI Premium',
                'stock' => 50,
                'price' => 62500,
                'size_label' => 'meter',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        $this->assertSame('Batik Siswa MI Premium', $product->name);
        $this->assertSame('50.00', $product->stock);
        $this->assertSame('62500.00', $product->price);
        $this->assertSame('meter', $product->size_label);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_admin_can_hide_product_from_catalog_without_deleting_order_history(): void
    {
        $product = BatikProduct::ensureDefaults($this->foundation)->firstOrFail();
        $product->update(['stock' => 8]);
        $order = BatikOrder::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'product_id' => $product->getKey(),
            'school_id' => $this->school->getKey(),
            'ordered_at' => now(),
            'quantity' => 1,
            'total_amount' => $product->price,
            'status' => BatikOrder::STATUS_ORDERED,
        ]);

        Livewire::test(EditBatikProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('hideFromCatalog')
            ->assertNotified('Produk dihapus dari tampilan katalog');

        $this->assertFalse($product->fresh()->is_active);
        $this->assertDatabaseHas('batik_orders', ['id' => $order->getKey()]);
    }

    public function test_order_calculates_bill_and_reduces_stock_automatically(): void
    {
        $product = BatikProduct::ensureDefaults($this->foundation)->last();
        $product->update(['stock' => 10, 'price' => 94000]);

        Livewire::withQueryParams(['product' => $product->getKey()])
            ->test(CreateBatikOrder::class)
            ->fillForm([
                'school_id' => $this->school->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => 2,
                'status' => BatikOrder::STATUS_COLLECTED,
                'recipient_name' => 'Pak Andar',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = BatikOrder::query()->sole();

        $this->assertSame('188000.00', $order->total_amount);
        $this->assertSame('8.00', $product->fresh()->stock);

        $this->get(BatikOrderResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('MI YAPPI Baleharjo')
            ->assertSee('Rp. 188.000')
            ->assertSee('Sudah Diambil')
            ->assertSee('Pak Andar')
            ->assertSee('Delete');
    }

    public function test_legacy_order_displays_its_original_student_and_teacher_breakdown(): void
    {
        $product = BatikProduct::ensureDefaults($this->foundation)->firstOrFail();

        BatikOrder::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'product_id' => $product->getKey(),
            'school_id' => $this->school->getKey(),
            'ordered_at' => now(),
            'quantity' => 18,
            'total_amount' => 975000,
            'status' => BatikOrder::STATUS_COLLECTED,
            'recipient_name' => 'Pak Hasan',
            'legacy_order_id' => 501,
            'legacy_snapshot' => [
                'siswa' => 12,
                'guru_2m' => 2,
                'guru_25m' => 4,
                'keterangan' => 'Pesanan migrasi terverifikasi',
            ],
        ]);

        $this->get(BatikOrderResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Guru')
            ->assertDontSee('Guru 2 m')
            ->assertDontSee('Guru 2,5 m')
            ->assertSee('Pesanan migrasi terverifikasi')
            ->assertSee('Pak Hasan')
            ->assertSee('Sudah Diambil');
    }

    public function test_deleting_order_restores_product_stock(): void
    {
        $product = BatikProduct::ensureDefaults($this->foundation)->firstOrFail();
        $product->update(['stock' => 8]);
        $order = BatikOrder::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'product_id' => $product->getKey(),
            'school_id' => $this->school->getKey(),
            'ordered_at' => now(),
            'quantity' => 2,
            'total_amount' => 117000,
            'status' => BatikOrder::STATUS_ORDERED,
        ]);

        $order->delete();

        $this->assertSame('10.00', $product->fresh()->stock);
    }

    public function test_parent_admin_can_mark_an_order_as_received_with_recipient_name(): void
    {
        $product = BatikProduct::ensureDefaults($this->foundation)->firstOrFail();
        $order = BatikOrder::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'product_id' => $product->getKey(),
            'school_id' => $this->school->getKey(),
            'ordered_at' => now(),
            'quantity' => 2,
            'total_amount' => 117000,
            'status' => BatikOrder::STATUS_ORDERED,
        ]);

        Livewire::test(ListBatikOrders::class)
            ->assertSee('ACTION')
            ->assertTableActionVisible('accept', $order)
            ->callTableAction('accept', $order, data: [
                'recipient_name' => 'Bapak Ahmad',
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Pesanan berhasil diterima');

        $this->assertDatabaseHas('batik_orders', [
            'id' => $order->getKey(),
            'status' => BatikOrder::STATUS_COLLECTED,
            'recipient_name' => 'Bapak Ahmad',
        ]);
    }

    public function test_school_admin_order_is_shared_with_parent_admin_and_scoped_to_assigned_school(): void
    {
        $otherSchool = School::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'name' => 'MI YAPPI Sekolah Lain',
            'npsn' => '87654321',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $this->foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        $product = BatikProduct::ensureDefaults($this->foundation)->last();
        $product->update(['stock' => 10, 'price' => 94000]);
        BatikOrder::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'product_id' => $product->getKey(),
            'school_id' => $otherSchool->getKey(),
            'ordered_at' => now(),
            'quantity' => 1,
            'total_amount' => 94000,
            'status' => BatikOrder::STATUS_ORDERED,
        ]);

        $this->actingAs($schoolAdmin);
        filament()->setCurrentPanel(filament()->getPanel('app'));

        $this->get('/app')
            ->assertOk()
            ->assertSee('Pesanan Batik');
        $this->get(BatikOrderResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk()
            ->assertSee('Pemesanan Kain Seragam Batik')
            ->assertSee('Pesan Sekarang')
            ->assertDontSee($otherSchool->name);

        Livewire::withQueryParams(['product' => $product->getKey()])
            ->test(CreateBatikOrder::class)
            ->fillForm([
                'school_id' => $this->school->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => 2,
                'status' => BatikOrder::STATUS_COLLECTED,
                'recipient_name' => 'Admin MI Baleharjo',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $schoolOrder = BatikOrder::query()
            ->where('school_id', $this->school->getKey())
            ->sole();
        $this->assertSame(BatikOrder::STATUS_ORDERED, $schoolOrder->status);
        $this->assertSame('188000.00', $schoolOrder->total_amount);

        $parentAdmin = User::factory()->create();
        $parentAdmin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($parentAdmin);
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->get(BatikOrderResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee($this->school->name)
            ->assertSee($otherSchool->name)
            ->assertSee('Rp. 188.000')
            ->assertSee('Diterima');
    }
}
