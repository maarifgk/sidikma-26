<?php

namespace Tests\Feature;

use App\Filament\Resources\Foundations\FoundationResource;
use App\Filament\Resources\Foundations\Pages\ListFoundations;
use App\Filament\Resources\Schools\Pages\EditSchool;
use App\Filament\Resources\Schools\Pages\ListSchools;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentResourceAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);

        $this->travelTo('2026-08-07 12:00:00');
    }

    public function test_admin_induk_sees_all_records_in_filament_resources(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('SATU', '11111111');
        [$secondFoundation, $secondSchool] = $this->createOrganization('DUA', '22222222');
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        Livewire::test(ListFoundations::class)
            ->assertCanSeeTableRecords([$firstFoundation, $secondFoundation]);

        Livewire::test(ListSchools::class)
            ->assertCanSeeTableRecords([$firstSchool, $secondSchool]);
    }

    public function test_scoped_roles_only_see_their_assigned_records_in_filament_resources(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('SATU', '11111111');
        [$secondFoundation, $secondSchool] = $this->createOrganization('DUA', '22222222');

        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->assignMembership($schoolAdmin, $firstFoundation, $firstSchool);
        $this->actingAs($schoolAdmin);

        Livewire::test(ListFoundations::class)
            ->assertCanSeeTableRecords([$firstFoundation])
            ->assertCanNotSeeTableRecords([$secondFoundation]);

        Livewire::test(ListSchools::class)
            ->assertCanSeeTableRecords([$firstSchool])
            ->assertCanNotSeeTableRecords([$secondSchool]);

        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->assignMembership($teacher, $secondFoundation, $secondSchool);
        $this->actingAs($teacher);

        Livewire::test(ListFoundations::class)
            ->assertCanSeeTableRecords([$secondFoundation])
            ->assertCanNotSeeTableRecords([$firstFoundation]);

        Livewire::test(ListSchools::class)
            ->assertCanSeeTableRecords([$secondSchool])
            ->assertCanNotSeeTableRecords([$firstSchool]);
    }

    public function test_user_without_access_scope_sees_no_organizational_records(): void
    {
        $this->createOrganization('TERTUTUP', '33333333');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(FoundationResource::getUrl())->assertForbidden();
        $this->get(SchoolResource::getUrl())->assertForbidden();
    }

    public function test_single_foundation_school_form_hides_foundation_and_phone_fields(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('SATU', '11111111');
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->assignMembership($schoolAdmin, $firstFoundation, $firstSchool);
        $this->actingAs($schoolAdmin);

        Livewire::test(EditSchool::class, ['record' => $firstSchool->getRouteKey()])
            ->assertFormFieldDoesNotExist('foundation_id')
            ->assertFormFieldDoesNotExist('phone');
    }

    public function test_trashed_filter_does_not_expose_another_users_school(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('SATU', '11111111');
        [, $secondSchool] = $this->createOrganization('DUA', '22222222');
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->assignMembership($schoolAdmin, $firstFoundation, $firstSchool);
        $firstSchool->delete();
        $secondSchool->delete();
        $this->actingAs($schoolAdmin);

        Livewire::test(ListSchools::class)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$firstSchool])
            ->assertCanNotSeeTableRecords([$secondSchool]);
    }

    /**
     * @return array{Foundation, School}
     */
    private function createOrganization(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-RESOURCE-{$suffix}",
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Sekolah {$suffix}",
            'npsn' => $npsn,
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        return [$foundation, $school];
    }

    private function assignMembership(User $user, Foundation $foundation, School $school): Membership
    {
        return Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
    }
}
