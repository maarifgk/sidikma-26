<?php
namespace App\Services;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Employee;
class AttendanceEventService
{
 public function record(Employee $employee, string $type, string $status, array $data = []): AttendanceEvent
 {
  $data['device_info'] ??= $this->deviceInfo();
  $data['selfie_path'] = $status === 'rejected' ? null : ($data['selfie_path'] ?? null);
  $event = AttendanceEvent::query()->create(array_merge(['user_id'=>$employee->user_id,'employee_id'=>$employee->id,'school_id'=>$employee->school_id,'attendance_date'=>now(config('attendance.timezone'))->toDateString(),'event_type'=>$type,'event_status'=>$status,'checked_at'=>now(config('attendance.timezone'))], $data));
  app(AuditLogService::class)->record($status === 'accepted' ? $type.'_accepted' : $data['rejection_code'] ?? $type.'_rejected', $event, $data, [], ['target_user_id'=>$employee->user_id,'employee_id'=>$employee->id,'school_id'=>$employee->school_id]);
  return $event;
 }
 public function accepted(Employee $employee, AttendanceRecord $attendance, string $type, array $data = []): AttendanceEvent
 { return $this->record($employee,$type,'accepted',array_merge(['attendance_id'=>$attendance->id,'rejection_code'=>null,'rejection_reason'=>null],$data)); }
 private function deviceInfo(): ?string { $agent = request()->userAgent(); return $agent ? mb_substr(preg_replace('/[^\x20-\x7E]/', '', $agent), 0, 255) : null; }
}
