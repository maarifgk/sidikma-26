<?php

namespace Tests\Feature;

use App\Filament\App\Pages\MyComplaints;
use App\Filament\App\Pages\MyDecrees;
use App\Filament\App\Pages\MyPayments;
use App\Filament\App\Pages\MyProfile;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherMobilePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_all_personal_mobile_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_GURU_PEGAWAI);
        Employee::factory()->create(['user_id' => $user->getKey()]);
        $this->actingAs($user);

        foreach ([MyPayments::class, MyDecrees::class, MyProfile::class, MyComplaints::class] as $page) {
            $this->get($page::getUrl(panel: 'app', isAbsolute: false))
                ->assertOk()
                ->assertSee('SIDIKMA Mobile');
        }
    }

    public function test_school_admin_cannot_open_teacher_personal_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($user);

        foreach ([MyPayments::class, MyDecrees::class, MyProfile::class, MyComplaints::class] as $page) {
            $this->get($page::getUrl(panel: 'app', isAbsolute: false))->assertForbidden();
        }
    }
}
