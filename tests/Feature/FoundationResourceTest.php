<?php

namespace Tests\Feature;

use App\Filament\Resources\Foundations\Pages\CreateFoundation;
use App\Filament\Resources\Foundations\Pages\EditFoundation;
use App\Filament\Resources\Foundations\Pages\ListFoundations;
use App\Models\Foundation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FoundationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(User::ROLE_ADMIN_INDUK);

        $this->actingAs($user);
    }

    public function test_foundation_can_be_created_with_valid_data(): void
    {
        Livewire::test(CreateFoundation::class)
            ->set('data.name', 'Yayasan Pendidikan Nusantara')
            ->set('data.code', '  ypn-001  ')
            ->set('data.phone', '+62 812-3456-7890')
            ->set('data.email', 'info@example.test')
            ->set('data.address', 'Jalan Pendidikan Nomor 1')
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('foundations', [
            'name' => 'Yayasan Pendidikan Nusantara',
            'code' => 'YPN-001',
            'phone' => '+62 812-3456-7890',
            'email' => 'info@example.test',
            'is_active' => true,
        ]);
    }

    public function test_foundation_form_rejects_invalid_and_duplicate_data(): void
    {
        Foundation::query()->create([
            'name' => 'Yayasan Pertama',
            'code' => 'YYS-001',
            'is_active' => true,
        ]);

        Livewire::test(CreateFoundation::class)
            ->set('data.name', '')
            ->set('data.code', ' yys-001 ')
            ->set('data.phone', 'telepon-tidak-valid')
            ->set('data.email', 'email-tidak-valid')
            ->set('data.address', str_repeat('A', 2001))
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'code' => 'unique',
                'phone' => 'regex',
                'email' => 'email',
                'address' => 'max',
            ]);

        $this->assertSame(1, Foundation::query()->count());
    }

    public function test_foundation_can_be_soft_deleted_from_edit_page(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Untuk Dihapus',
            'code' => 'YYS-DELETE',
            'is_active' => true,
        ]);

        Livewire::test(EditFoundation::class, ['record' => $foundation->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertNull(Foundation::query()->find($foundation->getKey()));
        $this->assertNotNull(Foundation::withTrashed()->find($foundation->getKey()));
        $this->assertSoftDeleted('foundations', ['id' => $foundation->getKey()]);
    }

    public function test_soft_deleted_foundation_can_be_restored_from_edit_page(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Untuk Dipulihkan',
            'code' => 'YYS-RESTORE',
            'is_active' => true,
        ]);

        $foundation->delete();

        Livewire::test(EditFoundation::class, ['record' => $foundation->getRouteKey()])
            ->callAction(RestoreAction::class)
            ->assertHasNoActionErrors();

        $this->assertNotNull(Foundation::query()->find($foundation->getKey()));
        $this->assertDatabaseHas('foundations', [
            'id' => $foundation->getKey(),
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_foundation_can_be_permanently_deleted_from_edit_page(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Untuk Dihapus Permanen',
            'code' => 'YYS-FORCE-DELETE',
            'is_active' => true,
        ]);

        $foundation->delete();

        Livewire::test(EditFoundation::class, ['record' => $foundation->getRouteKey()])
            ->callAction(ForceDeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertNull(Foundation::withTrashed()->find($foundation->getKey()));
        $this->assertDatabaseMissing('foundations', ['id' => $foundation->getKey()]);
    }

    public function test_foundation_list_displays_records_and_can_be_searched(): void
    {
        $nusantara = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Nusantara',
            'code' => 'YPN-001',
            'is_active' => true,
        ]);

        $maarif = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Ma\'arif',
            'code' => 'YPM-002',
            'is_active' => true,
        ]);

        $deleted = Foundation::query()->create([
            'name' => 'Yayasan Sudah Dihapus',
            'code' => 'YSD-003',
            'is_active' => false,
        ]);

        $deleted->delete();

        Livewire::test(ListFoundations::class)
            ->assertCanSeeTableRecords([$nusantara, $maarif])
            ->assertCanNotSeeTableRecords([$deleted])
            ->searchTable('Nusantara')
            ->assertCanSeeTableRecords([$nusantara])
            ->assertCanNotSeeTableRecords([$maarif]);

        Livewire::test(ListFoundations::class)
            ->searchTable('YPM-002')
            ->assertCanSeeTableRecords([$maarif])
            ->assertCanNotSeeTableRecords([$nusantara]);
    }

    public function test_foundation_can_be_updated_from_edit_page(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Nama Yayasan Lama',
            'code' => 'YYS-LAMA',
            'is_active' => true,
        ]);

        Livewire::test(EditFoundation::class, ['record' => $foundation->getRouteKey()])
            ->set('data.name', 'Nama Yayasan Baru')
            ->set('data.code', '  yys-baru  ')
            ->set('data.phone', '+62 274 123456')
            ->set('data.email', 'baru@example.test')
            ->set('data.address', 'Alamat yayasan yang baru')
            ->set('data.is_active', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('foundations', [
            'id' => $foundation->getKey(),
            'name' => 'Nama Yayasan Baru',
            'code' => 'YYS-BARU',
            'phone' => '+62 274 123456',
            'email' => 'baru@example.test',
            'address' => 'Alamat yayasan yang baru',
            'is_active' => false,
        ]);
    }

    public function test_foundation_code_must_remain_unique_when_updated(): void
    {
        Foundation::query()->create([
            'name' => 'Yayasan Pertama',
            'code' => 'YYS-001',
            'is_active' => true,
        ]);

        $secondFoundation = Foundation::query()->create([
            'name' => 'Yayasan Kedua',
            'code' => 'YYS-002',
            'is_active' => true,
        ]);

        Livewire::test(EditFoundation::class, ['record' => $secondFoundation->getRouteKey()])
            ->set('data.code', ' yys-001 ')
            ->call('save')
            ->assertHasFormErrors(['code' => 'unique']);

        $this->assertDatabaseHas('foundations', [
            'id' => $secondFoundation->getKey(),
            'code' => 'YYS-002',
        ]);
    }

    public function test_foundation_can_keep_its_own_code_when_updated(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Tetap',
            'code' => 'YYS-TETAP',
            'is_active' => true,
        ]);

        Livewire::test(EditFoundation::class, ['record' => $foundation->getRouteKey()])
            ->set('data.name', 'Yayasan Tetap Diperbarui')
            ->set('data.code', ' yys-tetap ')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('foundations', [
            'id' => $foundation->getKey(),
            'name' => 'Yayasan Tetap Diperbarui',
            'code' => 'YYS-TETAP',
        ]);
    }
}
