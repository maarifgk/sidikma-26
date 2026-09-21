<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ScopesAttendanceToAccessibleSchools;
use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceRecord;
use BackedEnum;
use Carbon\CarbonPeriod;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;
use App\Services\AuditLogService;

class AttendanceLeaveRequests extends Page
{
    use ScopesAttendanceToAccessibleSchools;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Pengajuan Izin';

    protected static ?string $slug = 'presensi/pengajuan-izin';

    protected static bool $shouldRegisterNavigation = false;

    public string $search = '';

    public string $selectedStatus = '';

    /** @var array<int, string> */
    public array $reviewNotes = [];

    public static function canAccess(): bool
    {
        return static::canUseAttendanceManagement() && (auth()->user()?->can('attendance.leave.review') ?? false);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.pages.attendance-leave-requests')
                ->viewData(fn (): array => $this->pageData()),
        ]);
    }

    public function approve(int $requestId): void
    {
        $this->review($requestId, AttendanceLeaveRequest::STATUS_APPROVED);
    }

    public function reject(int $requestId): void
    {
        $this->review($requestId, AttendanceLeaveRequest::STATUS_REJECTED);
    }

    /** @return array<string, mixed> */
    private function pageData(): array
    {
        $search = trim($this->search);

        return [
            'requests' => AttendanceLeaveRequest::query()
                ->whereIn('school_id', $this->accessibleAttendanceSchoolIds())
                ->when(filled($this->selectedStatus), fn (Builder $query): Builder => $query->where('status', $this->selectedStatus))
                ->when(filled($search), fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('employee', fn (Builder $query): Builder => $query->whereLike('name', "%{$search}%"))
                        ->orWhereHas('school', fn (Builder $query): Builder => $query->whereLike('name', "%{$search}%"))
                        ->orWhereLike('reason', "%{$search}%");
                }))
                ->with(['employee', 'school'])
                ->latest()
                ->limit(100)
                ->get(),
            'typeOptions' => AttendanceLeaveRequest::typeOptions(),
            'statusOptions' => AttendanceLeaveRequest::statusOptions(),
            'dashboardUrl' => AttendanceDashboard::getUrl(panel: $this->attendancePanelId(), isAbsolute: false),
        ];
    }

    private function review(int $requestId, string $status): void
    {
        $request = AttendanceLeaveRequest::query()
            ->whereIn('school_id', $this->accessibleAttendanceSchoolIds())
            ->where('status', AttendanceLeaveRequest::STATUS_PENDING)
            ->findOrFail($requestId);

        $oldStatus = $request->status; $oldNotes = $request->review_notes;
        DB::transaction(function () use ($request, $status): void {
            $request->update([
                'status' => $status,
                'review_notes' => $this->reviewNotes[$request->getKey()] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            if ($status !== AttendanceLeaveRequest::STATUS_APPROVED) {
                return;
            }

            $attendanceStatus = $request->leave_type === AttendanceLeaveRequest::TYPE_LEAVE
                ? AttendanceRecord::STATUS_LEAVE
                : AttendanceRecord::STATUS_PERMIT;

            foreach (CarbonPeriod::create($request->start_date, $request->end_date) as $date) {
                AttendanceRecord::query()->firstOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'attendance_date' => Carbon::parse($date)->toDateString(),
                    ],
                    [
                        'user_id' => $request->user_id,
                        'school_id' => $request->school_id,
                        'status' => $attendanceStatus,
                        'notes' => $request->reason,
                    ],
                );
            }
        });
        app(AuditLogService::class)->record($status === AttendanceLeaveRequest::STATUS_APPROVED ? 'attendance_leave_approved' : 'attendance_leave_rejected', $request->fresh(), ['status'=>$status,'review_notes'=>$request->review_notes], ['status'=>$oldStatus,'review_notes'=>$oldNotes], ['target_user_id'=>$request->user_id,'employee_id'=>$request->employee_id,'school_id'=>$request->school_id]);

        unset($this->reviewNotes[$requestId]);
        Notification::make()->success()->title($status === AttendanceLeaveRequest::STATUS_APPROVED ? 'Pengajuan disetujui' : 'Pengajuan ditolak')->send();
    }
}
