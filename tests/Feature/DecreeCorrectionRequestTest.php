<?php

namespace Tests\Feature;

use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use App\Models\DecreeCorrectionRequest;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecreeCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_is_strictly_scoped_to_own_school_and_admin_induk_sees_all(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $foundation=Foundation::query()->create(['name'=>'Yayasan Test','code'=>'YYS-PSK','is_active'=>true]);
        $first=School::query()->create(['foundation_id'=>$foundation->id,'name'=>'MI Satu','npsn'=>'11112222','school_level'=>'MI','is_active'=>true]);
        $second=School::query()->create(['foundation_id'=>$foundation->id,'name'=>'MI Dua','npsn'=>'33334444','school_level'=>'MI','is_active'=>true]);
        $schoolAdmin=User::factory()->create(); $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create(['user_id'=>$schoolAdmin->id,'foundation_id'=>$foundation->id,'school_id'=>$first->id,'status'=>'active','start_date'=>today()]);
        $otherAdmin=User::factory()->create(); $otherAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create(['user_id'=>$otherAdmin->id,'foundation_id'=>$foundation->id,'school_id'=>$second->id,'status'=>'active','start_date'=>today()]);
        $own=$this->makeRequest($first,$schoolAdmin,'PSK/2026/08/0001');
        $other=$this->makeRequest($second,$otherAdmin,'PSK/2026/08/0002');

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($schoolAdmin);
        $this->assertTrue(DecreeCorrectionRequestResource::getEloquentQuery()->whereKey($own)->exists());
        $this->assertFalse(DecreeCorrectionRequestResource::getEloquentQuery()->whereKey($other)->exists());
        $this->get(DecreeCorrectionRequestResource::getUrl('view',['record'=>$other],panel:'app',isAbsolute:false))->assertNotFound();

        $admin=User::factory()->create(); $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin); filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->assertSame(2,DecreeCorrectionRequestResource::getEloquentQuery()->count());
        $this->get(DecreeCorrectionRequestResource::getUrl('view',['record'=>$other],panel:'admin',isAbsolute:false))->assertOk()->assertSee('DATA LAMA')->assertSee('DATA BARU');
    }

    public function test_status_helpers_and_number_generation(): void
    {
        $this->assertSame('warning',DecreeCorrectionRequest::statusColor('diajukan'));
        $this->assertSame('success',DecreeCorrectionRequest::statusColor('disetujui'));
        $this->assertMatchesRegularExpression('/^PSK\/\d{4}\/\d{2}\/0001$/',DecreeCorrectionRequest::generateRequestNumber());
    }

    private function makeRequest(School $school,User $user,string $number): DecreeCorrectionRequest
    {
        return DecreeCorrectionRequest::query()->create(['school_id'=>$school->id,'submitted_by'=>$user->id,'request_number'=>$number,'request_date'=>today(),'decree_number'=>'SK-01','decree_date'=>today(),'subject_name'=>'Nama Guru','correction_part'=>'tmt','old_data'=>'20 April 2022','new_data'=>'25 April 2022','reason'=>'Kesalahan TMT','old_decree_path'=>'decree-corrections/old/sk.pdf','status'=>'diajukan','submitted_at'=>now()]);
    }
}
