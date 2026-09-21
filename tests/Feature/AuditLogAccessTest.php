<?php
namespace Tests\Feature;
use App\Models\AuditLog;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolePermissionSeeder;
class AuditLogAccessTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp(): void { parent::setUp(); $this->seed(RolePermissionSeeder::class); }
 public function test_school_admin_is_scoped_and_regular_user_is_denied(): void
 {
  $foundation=Foundation::factory()->create(); $one=School::create(['foundation_id'=>$foundation->id,'name'=>'Satu','npsn'=>'11111111','school_level'=>'MI','is_active'=>true]); $two=School::create(['foundation_id'=>$foundation->id,'name'=>'Dua','npsn'=>'22222222','school_level'=>'MI','is_active'=>true]);
  $admin=User::factory()->create(); $admin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH); Membership::create(['user_id'=>$admin->id,'foundation_id'=>$foundation->id,'school_id'=>$one->id,'status'=>'active']);
  AuditLog::create(['actor_user_id'=>$admin->id,'school_id'=>$one->id,'action'=>'one','entity_type'=>'test','new_values'=>['ok'=>1]]); AuditLog::create(['actor_user_id'=>$admin->id,'school_id'=>$two->id,'action'=>'two','entity_type'=>'test']);
  $this->assertCount(1, app(AuditLogQueryService::class)->forUser($admin)->get());
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class); app(AuditLogQueryService::class)->forUser(User::factory()->create());
 }
 public function test_audit_service_sanitizes_user_agent(): void
 {
  $user=User::factory()->create(); $this->actingAs($user); request()->headers->set('User-Agent', str_repeat('x',400));
  $log=app(\App\Services\AuditLogService::class)->record('test','TestEntity',['password'=>'should-not-be-used']);
  $this->assertLessThanOrEqual(255, strlen($log->user_agent)); $this->assertNull($log->old_values); $this->assertNull($log->new_values);
 }
}
