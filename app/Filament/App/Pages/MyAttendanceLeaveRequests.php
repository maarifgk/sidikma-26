<?php

namespace App\Filament\App\Pages;

use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use UnitEnum;
use App\Services\AuditLogService;

class MyAttendanceLeaveRequests extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Pengajuan Izin';

    protected static ?string $slug = 'presensi-saya/pengajuan-izin';

    protected static bool $shouldRegisterNavigation = false;

    public string $leaveType = AttendanceLeaveRequest::TYPE_PERMIT;

    public string $startDate = '';

    public string $endDate = '';

    public string $reason = '';

    public mixed $attachment = null;

    public function mount(): void
    {
        $this->startDate = now('Asia/Jakarta')->toDateString();
        $this->endDate = $this->startDate;
    }

    public static function canAccess(): bool
    {
        return Employee::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->whereNotNull('school_id')
            ->exists();
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.app.pages.my-attendance-leave-requests')
                ->viewData(fn (): array => $this->pageData()),
        ]);
    }

    public function submitRequest(): void
    {
        $validated = $this->validate([
            'leaveType' => ['required', 'in:'.implode(',', array_keys(AttendanceLeaveRequest::typeOptions()))],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $employee = Employee::query()->where('user_id', auth()->id())->where('is_active', true)->whereNotNull('school_id')->first();

        if (! $employee) {
            $this->addError('attendance', 'Akun belum terhubung dengan data guru/pegawai dan sekolah.');

            return;
        }

        $overlap = AttendanceLeaveRequest::query()
            ->where('employee_id', $employee->getKey())
            ->whereIn('status', [AttendanceLeaveRequest::STATUS_PENDING, AttendanceLeaveRequest::STATUS_APPROVED])
            ->whereDate('start_date', '<=', $validated['endDate'])
            ->whereDate('end_date', '>=', $validated['startDate'])
            ->exists();

        if ($overlap) {
            $this->addError('startDate', 'Sudah ada pengajuan pada rentang tanggal tersebut.');

            return;
        }

        $request = AttendanceLeaveRequest::query()->create([
            'user_id' => auth()->id(),
            'employee_id' => $employee->getKey(),
            'school_id' => $employee->school_id,
            'leave_type' => $validated['leaveType'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'reason' => $validated['reason'],
            'attachment_path' => $this->attachment?->store("leave-requests/{$employee->school_id}", AttendanceRecord::DISK),
            'status' => AttendanceLeaveRequest::STATUS_PENDING,
        ]);
        app(AuditLogService::class)->record('attendance_leave_submitted', $request, $request->only(['leave_type','start_date','end_date','reason','status']), [], ['target_user_id'=>auth()->id(),'employee_id'=>$employee->id,'school_id'=>$employee->school_id]);

        $this->reset('reason', 'attachment');
        Notification::make()->success()->title('Pengajuan izin dikirim')->send();
    }

    /** @return array<string, mixed> */
    private function pageData(): array
    {
        return [
            'typeOptions' => AttendanceLeaveRequest::typeOptions(),
            'statusOptions' => AttendanceLeaveRequest::statusOptions(),
            'requests' => AttendanceLeaveRequest::query()->where('user_id', auth()->id())->latest()->limit(50)->get(),
            'attendanceUrl' => MyAttendance::getUrl(panel: 'app', isAbsolute: false),
        ];
    }
}
