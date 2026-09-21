<?php
namespace Tests\Feature;
use App\Filament\Admin\Pages\AttendanceReport;
use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\XlsxReader;
use Tests\TestCase;
class AttendanceReportExportFeatureTest extends TestCase
{
 use RefreshDatabase; private User $root; private User $schoolAdmin; private School $one; private School $two; private Employee $employee;
 protected function setUp(): void { parent::setUp(); $this->seed(RolePermissionSeeder::class); $f=Foundation::factory()->create(); $this->one=School::create(['foundation_id'=>$f->id,'name'=>'Sekolah Satu','npsn'=>'12345678','school_level'=>'MI','is_active'=>true]); $this->two=School::create(['foundation_id'=>$f->id,'name'=>'Sekolah Dua','npsn'=>'87654321','school_level'=>'MI','is_active'=>true]); $this->root=User::factory()->create(); $this->root->assignRole(User::ROLE_ADMIN_INDUK); $this->schoolAdmin=User::factory()->create(); $this->schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH); Membership::create(['user_id'=>$this->schoolAdmin->id,'foundation_id'=>$f->id,'school_id'=>$this->one->id,'status'=>'active']); $this->employee=Employee::factory()->create(['foundation_id'=>$f->id,'school_id'=>$this->one->id,'user_id'=>$this->root->id]); AttendanceSetting::forSchool($this->one->id); AttendanceSetting::forSchool($this->two->id); }
 private function seedRows(): void { $otherUser=User::factory()->create(); $other=Employee::factory()->create(['foundation_id'=>$this->one->foundation_id,'school_id'=>$this->two->id,'user_id'=>$otherUser->id]); AttendanceRecord::create(['user_id'=>$this->root->id,'employee_id'=>$this->employee->id,'school_id'=>$this->one->id,'attendance_date'=>'2026-09-10','status'=>'late','check_in_at'=>'2026-09-10 01:00:00','check_out_reason'=>'Rapat dinas','check_in_distance'=>12,'check_out_distance'=>20]); AttendanceRecord::create(['user_id'=>$otherUser->id,'employee_id'=>$other->id,'school_id'=>$this->two->id,'attendance_date'=>'2026-09-20','status'=>'present']); foreach([AttendanceLeaveRequest::STATUS_PENDING,AttendanceLeaveRequest::STATUS_APPROVED,AttendanceLeaveRequest::STATUS_REJECTED] as $i=>$status){AttendanceLeaveRequest::create(['user_id'=>$this->root->id,'employee_id'=>$this->employee->id,'school_id'=>$this->one->id,'leave_type'=>'permit','start_date'=>Carbon::parse('2026-09-'.(10+$i))->toDateString(),'end_date'=>Carbon::parse('2026-09-'.(10+$i))->toDateString(),'reason'=>'Keperluan '.$status,'status'=>$status,'reviewed_by'=>$status==='pending'?null:$this->root->id,'review_notes'=>$status==='pending'?null:'Catatan '.$status,'reviewed_at'=>$status==='pending'?null:'2026-09-11 01:00:00']); } }
 private function export(string $from,string $to,?int $school=null): XlsxReader { $this->actingAs($this->root); $component=Livewire::test(AttendanceReport::class)->set('dateFrom',$from)->set('dateTo',$to); if($school){$component->set('selectedSchoolId',$school);} $response=$component->instance()->exportReport(); ob_start(); $response->sendContent(); $bytes=ob_get_clean(); return new XlsxReader($bytes); }
 public function test_superadmin_export_and_period_school_filter_keep_other_school_out(): void { $this->seedRows(); $reader=$this->export('2026-09-01','2026-09-15',$this->one->id); $this->assertStringContainsString('Presensi',$reader->workbook()); $this->assertStringContainsString('Izin',$reader->workbook()); $this->assertTrue($reader->contains(1,'Sekolah Satu')); $this->assertFalse($reader->contains(1,'Sekolah Dua')); $this->assertTrue($reader->contains(2,'pending')); $this->assertTrue($reader->contains(2,'approved')); $this->assertTrue($reader->contains(2,'rejected')); $this->assertTrue($reader->contains(2,'Catatan approved')); $this->assertFalse($reader->contains(1,'2026-09-20')); $reader->close(); }
 public function test_school_admin_cannot_export_other_school(): void { $this->seedRows(); $this->actingAs($this->schoolAdmin); $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class); $page=app(AttendanceReport::class); $page->selectedSchoolId=$this->two->id; $page->exportReport(); }
 public function test_regular_user_cannot_access_report(): void { $this->actingAs(User::factory()->create()); $this->assertFalse(AttendanceReport::canAccess()); }
 public function test_superadmin_can_export_all_schools(): void { $this->seedRows(); $reader=$this->export('2026-09-01','2026-09-30'); $this->assertTrue($reader->contains(1,'Sekolah Satu')); $this->assertTrue($reader->contains(1,'Sekolah Dua')); $this->assertContains('Tanggal',$reader->headers(1)); $this->assertContains('Status',$reader->headers(2)); $reader->close(); }
 public function test_month_period_and_leave_statuses_are_exported(): void { $this->seedRows(); $this->actingAs($this->root); $component=Livewire::test(AttendanceReport::class)->set('reportType','monthly')->set('referenceDate','2026-09-15'); $response=$component->instance()->exportReport(); ob_start(); $response->sendContent(); $reader=new XlsxReader((string) ob_get_clean()); $this->assertTrue($reader->contains(2,'pending')); $this->assertTrue($reader->contains(2,'approved')); $this->assertTrue($reader->contains(2,'rejected')); $this->assertTrue($reader->contains(2,'Catatan rejected')); $reader->close(); }
}
