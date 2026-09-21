<?php

namespace Tests\Feature;

use App\Filament\Resources\Schools\Pages\CreateSchool;
use App\Filament\Resources\Schools\Pages\EditSchool;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolResourceTest extends TestCase
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

    public function test_school_can_be_updated_from_edit_page(): void
    {
        $firstFoundation = $this->createFoundation('Yayasan Pertama', 'YYS-001');
        $school = $this->createSchool($firstFoundation, 'Sekolah Lama', '12345678', 'MI');

        Livewire::test(EditSchool::class, ['record' => $school->getRouteKey()])
            ->set('data.name', 'Sekolah Baru')
            ->set('data.npsn', ' 87654321 ')
            ->set('data.school_level', 'SMP')
            ->set('data.accreditation_status', 'A')
            ->set('data.accreditation_expiry_year', 2030)
            ->set('data.email', 'sekolah-baru@example.test')
            ->set('data.address', 'Alamat sekolah yang baru')
            ->set('data.land_status', 'Wakaf')
            ->set('data.land_area', 1500)
            ->set('data.has_land_certificate', true)
            ->set('data.has_bhpnu_ownership', true)
            ->set('data.is_active', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('schools', [
            'id' => $school->getKey(),
            'foundation_id' => $firstFoundation->getKey(),
            'name' => 'Sekolah Baru',
            'npsn' => '87654321',
            'school_level' => 'SMP',
            'accreditation_status' => 'A',
            'accreditation_expiry_year' => 2030,
            'email' => 'sekolah-baru@example.test',
            'address' => 'Alamat sekolah yang baru',
            'land_status' => 'Wakaf',
            'land_area' => '1500.00',
            'has_land_certificate' => true,
            'has_bhpnu_ownership' => true,
            'is_active' => false,
        ]);

        $this->assertTrue($school->fresh()->foundation->is($firstFoundation));
    }

    public function test_school_creation_automatically_uses_the_single_application_foundation(): void
    {
        Livewire::test(CreateSchool::class)
            ->assertFormFieldDoesNotExist('foundation_id')
            ->assertFormFieldDoesNotExist('phone')
            ->set('data.name', 'MI Ma\'arif Satu Yayasan')
            ->set('data.npsn', '60714112')
            ->set('data.school_level', 'MI')
            ->call('create')
            ->assertHasNoFormErrors();

        $foundation = Foundation::query()->sole();

        $this->assertSame(Foundation::APPLICATION_NAME, $foundation->name);
        $this->assertDatabaseHas('schools', [
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI Ma\'arif Satu Yayasan',
            'npsn' => '60714112',
        ]);
    }

    public function test_school_can_keep_its_own_npsn_when_updated(): void
    {
        $foundation = $this->createFoundation('Yayasan Tetap', 'YYS-TETAP');
        $school = $this->createSchool($foundation, 'Sekolah Tetap', '11223344', 'MTs');

        Livewire::test(EditSchool::class, ['record' => $school->getRouteKey()])
            ->set('data.name', 'Sekolah Tetap Diperbarui')
            ->set('data.npsn', ' 11223344 ')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('schools', [
            'id' => $school->getKey(),
            'name' => 'Sekolah Tetap Diperbarui',
            'npsn' => '11223344',
        ]);
    }

    public function test_land_ownership_descriptions_use_sudah_belum_dropdowns(): void
    {
        $foundation = $this->createFoundation('Yayasan Keterangan', 'YYS-KET');
        $school = $this->createSchool($foundation, 'Sekolah Keterangan', '22446688', 'MI');

        Livewire::test(EditSchool::class, ['record' => $school->getRouteKey()])
            ->assertSee('Keterangan Sertifikat Tanah')
            ->assertSee('Keterangan BHPNU')
            ->assertSee('Sudah')
            ->assertSee('Belum')
            ->set('data.has_land_certificate', '0')
            ->set('data.has_bhpnu_ownership', '1')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('schools', [
            'id' => $school->getKey(),
            'has_land_certificate' => false,
            'has_bhpnu_ownership' => true,
        ]);
    }

    public function test_school_cannot_use_another_schools_npsn(): void
    {
        $foundation = $this->createFoundation('Yayasan NPSN', 'YYS-NPSN');
        $firstSchool = $this->createSchool($foundation, 'Sekolah Pertama', '12344321', 'MI');
        $secondSchool = $this->createSchool($foundation, 'Sekolah Kedua', '56788765', 'SMP');

        Livewire::test(EditSchool::class, ['record' => $secondSchool->getRouteKey()])
            ->set('data.npsn', " {$firstSchool->npsn} ")
            ->call('save')
            ->assertHasFormErrors(['npsn' => 'unique']);

        $this->assertDatabaseHas('schools', [
            'id' => $secondSchool->getKey(),
            'npsn' => '56788765',
        ]);
    }

    public function test_school_can_be_soft_deleted_from_edit_page(): void
    {
        $foundation = $this->createFoundation('Yayasan Hapus', 'YYS-HAPUS');
        $school = $this->createSchool($foundation, 'Sekolah Dihapus', '13572468', 'MI');

        Livewire::test(EditSchool::class, ['record' => $school->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertNull(School::query()->find($school->getKey()));
        $this->assertNotNull(School::withTrashed()->find($school->getKey()));
        $this->assertSoftDeleted('schools', ['id' => $school->getKey()]);
    }

    public function test_soft_deleted_school_can_be_restored_from_edit_page(): void
    {
        $foundation = $this->createFoundation('Yayasan Restore', 'YYS-RESTORE');
        $school = $this->createSchool($foundation, 'Sekolah Dipulihkan', '24681357', 'MTs');
        $school->delete();

        Livewire::test(EditSchool::class, ['record' => $school->getRouteKey()])
            ->callAction(RestoreAction::class)
            ->assertHasNoActionErrors();

        $this->assertNotNull(School::query()->find($school->getKey()));
        $this->assertDatabaseHas('schools', [
            'id' => $school->getKey(),
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_school_can_be_permanently_deleted_from_edit_page(): void
    {
        $foundation = $this->createFoundation('Yayasan Force Delete', 'YYS-FORCE');
        $school = $this->createSchool($foundation, 'Sekolah Dihapus Permanen', '10293847', 'SMP');
        $school->delete();

        Livewire::test(EditSchool::class, ['record' => $school->getRouteKey()])
            ->callAction(ForceDeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertNull(School::withTrashed()->find($school->getKey()));
        $this->assertDatabaseMissing('schools', ['id' => $school->getKey()]);
        $this->assertNotNull(Foundation::query()->find($foundation->getKey()));
    }

    private function createFoundation(string $name, string $code): Foundation
    {
        return Foundation::query()->create([
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    private function createSchool(
        Foundation $foundation,
        string $name,
        string $npsn,
        string $schoolLevel,
    ): School {
        return School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => $name,
            'npsn' => $npsn,
            'school_level' => $schoolLevel,
            'is_active' => true,
        ]);
    }
}
