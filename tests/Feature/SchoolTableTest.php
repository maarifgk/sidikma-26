<?php

namespace Tests\Feature;

use App\Filament\Resources\Schools\Pages\ListSchools;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolTableTest extends TestCase
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

    public function test_school_table_can_be_searched_by_name_and_npsn(): void
    {
        [$nusantaraSchool, $maarifSchool] = $this->createSchoolRecords();

        Livewire::test(ListSchools::class)
            ->assertCanSeeTableRecords([$nusantaraSchool, $maarifSchool])
            ->searchTable('Ibtidaiyah Nusantara')
            ->assertCanSeeTableRecords([$nusantaraSchool])
            ->assertCanNotSeeTableRecords([$maarifSchool]);

        Livewire::test(ListSchools::class)
            ->searchTable('87654321')
            ->assertCanSeeTableRecords([$maarifSchool])
            ->assertCanNotSeeTableRecords([$nusantaraSchool]);

    }

    public function test_school_table_can_be_filtered_by_level_status_and_trashed_state(): void
    {
        [$nusantaraSchool, $maarifSchool, $deletedSchool] = $this->createSchoolRecords(includeDeleted: true);

        Livewire::test(ListSchools::class)
            ->filterTable('school_level', 'MTs')
            ->assertCanSeeTableRecords([$maarifSchool])
            ->assertCanNotSeeTableRecords([$nusantaraSchool, $deletedSchool]);

        Livewire::test(ListSchools::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$nusantaraSchool])
            ->assertCanNotSeeTableRecords([$maarifSchool, $deletedSchool]);

        Livewire::test(ListSchools::class)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$deletedSchool])
            ->assertCanNotSeeTableRecords([$nusantaraSchool, $maarifSchool]);
    }

    /**
     * @return array<int, School>
     */
    private function createSchoolRecords(bool $includeDeleted = false): array
    {
        $nusantara = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Nusantara',
            'code' => 'YPN-TABLE',
            'is_active' => true,
        ]);

        $maarif = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Ma\'arif',
            'code' => 'YPM-TABLE',
            'is_active' => true,
        ]);

        $nusantaraSchool = School::query()->create([
            'foundation_id' => $nusantara->getKey(),
            'name' => 'Madrasah Ibtidaiyah Nusantara',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        $maarifSchool = School::query()->create([
            'foundation_id' => $maarif->getKey(),
            'name' => 'Madrasah Tsanawiyah Ma\'arif',
            'npsn' => '87654321',
            'school_level' => 'MTs',
            'is_active' => false,
        ]);

        $schools = [$nusantaraSchool, $maarifSchool];

        if ($includeDeleted) {
            $deletedSchool = School::query()->create([
                'foundation_id' => $nusantara->getKey(),
                'name' => 'SMP Terhapus',
                'npsn' => '11223344',
                'school_level' => 'SMP',
                'is_active' => true,
            ]);

            $deletedSchool->delete();
            $schools[] = $deletedSchool;
        }

        return $schools;
    }
}
