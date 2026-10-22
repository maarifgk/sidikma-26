<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ScopesAttendanceToAccessibleSchools;
use App\Models\AttendanceRecord;
use App\Models\AttendanceEvent;
use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\School;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class AttendanceDashboard extends Page
{
    use ScopesAttendanceToAccessibleSchools;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Dashboard Presensi';

    protected static ?string $slug = 'presensi';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $selectedSchoolId = null;

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $today = now('Asia/Jakarta')->toDateString();
        $schoolIds = $this->accessibleAttendanceSchoolIds();
        $latestAttendanceDate = AttendanceRecord::query()
            ->whereIn('school_id', $schoolIds)
            ->max('attendance_date');

        $this->dateFrom = $latestAttendanceDate
            ? Carbon::parse($latestAttendanceDate)->toDateString()
            : $today;
        $this->dateTo = $this->dateFrom;
    }

    public static function canAccess(): bool
    {
        return static::canUseAttendanceManagement() && (auth()->user()?->can('attendance.view') ?? false);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.pages.attendance-dashboard')
                ->viewData(fn (): array => $this->pageData()),
        ]);
    }

    /** @return array<string, mixed> */
    private function pageData(): array
    {
        $today = now('Asia/Jakarta')->toDateString();
        $from = $this->dateFrom ?: $today;
        $to = $this->dateTo ?: $today;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 30) {
            $from = Carbon::parse($to)->subDays(30)->toDateString();
        }
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $schoolIds = $this->accessibleAttendanceSchoolIds();
        abort_if($this->selectedSchoolId && ! $schoolIds->contains($this->selectedSchoolId), 403);
        $employeeQuery = Employee::query()->whereIn('school_id', $schoolIds)
            ->where('is_active', true)
            ->when($this->selectedSchoolId, fn (Builder $query): Builder => $query->where('school_id', $this->selectedSchoolId));
        $recordQuery = AttendanceRecord::query()->whereIn('school_id', $schoolIds)
            ->whereBetween('attendance_date', [$from, $to])
            ->when($this->selectedSchoolId, fn (Builder $query): Builder => $query->where('school_id', $this->selectedSchoolId));
        $totalEmployees = (clone $employeeQuery)->distinct('id')->count('id');
        $statusEmployees = (clone $recordQuery)
            ->get(['employee_id', 'status'])
            ->groupBy('status')
            ->map(fn ($records) => $records->pluck('employee_id')->unique()->values());
        $totalRecords = (clone $recordQuery)->count();
        $presentEmployees = $statusEmployees->get(AttendanceRecord::STATUS_PRESENT, collect());
        $lateEmployees = $statusEmployees->get(AttendanceRecord::STATUS_LATE, collect());
        $recordPermitEmployees = $statusEmployees->get(AttendanceRecord::STATUS_PERMIT, collect());
        $recordLeaveEmployees = $statusEmployees->get(AttendanceRecord::STATUS_LEAVE, collect());
        $approvedLeaveQuery = AttendanceLeaveRequest::query()
            ->whereIn('school_id', $schoolIds)
            ->where('status', AttendanceLeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->when($this->selectedSchoolId, fn (Builder $query): Builder => $query->where('school_id', $this->selectedSchoolId));
        $permitEmployees = $recordPermitEmployees->merge(
            (clone $approvedLeaveQuery)->whereIn('leave_type', [AttendanceLeaveRequest::TYPE_PERMIT, AttendanceLeaveRequest::TYPE_SICK])->pluck('employee_id')
        )->unique()->values();
        $leaveEmployees = $recordLeaveEmployees->merge(
            (clone $approvedLeaveQuery)->where('leave_type', AttendanceLeaveRequest::TYPE_LEAVE)->pluck('employee_id')
        )->unique()->values();
        $present = $presentEmployees->count();
        $late = $lateEmployees->count();
        $permit = $permitEmployees->count();
        $leave = $leaveEmployees->count();
        $coveredEmployees = $presentEmployees->merge($lateEmployees)->merge($permitEmployees)->merge($leaveEmployees)->unique();
        $eventQuery = AttendanceEvent::query()->whereIn('school_id', $schoolIds)
            ->whereBetween('attendance_date', [$from, $to])
            ->when($this->selectedSchoolId, fn ($q) => $q->where('school_id', $this->selectedSchoolId));
        $rejected = (clone $eventQuery)->where('event_status', 'rejected')->count();
        $fakeGps = (clone $eventQuery)->where('is_mock_location', true)->count();
        $early = (clone $recordQuery)->whereNotNull('check_out_reason')->count();
        $mapRecords = (clone $recordQuery)
            ->whereNotNull('check_in_latitude')
            ->whereNotNull('check_in_longitude')
            ->with(['employee', 'school'])
            ->latest('check_in_at')
            ->limit(300)
            ->get();
        $mapSchoolIds = $this->selectedSchoolId
            ? collect([$this->selectedSchoolId])
            : $schoolIds;
        $mapSettings = AttendanceSetting::query()
            ->with('school')
            ->whereIn('school_id', $mapSchoolIds)
            ->whereNotNull('office_latitude')
            ->whereNotNull('office_longitude')
            ->get();

        $sevenDays = collect(range(6, 0))->map(function (int $daysAgo) use ($schoolIds, $to): array {
            $date = Carbon::parse($to, 'Asia/Jakarta')->subDays($daysAgo);
            $count = AttendanceRecord::query()
                ->whereIn('school_id', $schoolIds)
                ->whereDate('attendance_date', $date->toDateString())
                ->when($this->selectedSchoolId, fn (Builder $query): Builder => $query->where('school_id', $this->selectedSchoolId))
                ->whereIn('status', [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_LATE])
                ->count();

            return [
                'label' => $date->locale('id')->translatedFormat('D, d M'),
                'count' => $count,
            ];
        });
        $maxDay = max(1, (int) $sevenDays->max('count'));
        $startDate = Carbon::parse($from, 'Asia/Jakarta');
        $dayCount = min(30, max(0, (int) $startDate->diffInDays(Carbon::parse($to, 'Asia/Jakarta'))));
        $dailyCharts = collect(range(0, $dayCount))->map(function (int $offset) use ($startDate, $schoolIds): array {
            $date = $startDate->copy()->addDays($offset);
            $day = $date->toDateString();
            $base = AttendanceRecord::query()->whereIn('school_id', $schoolIds)->whereDate('attendance_date', $day)->when($this->selectedSchoolId, fn (Builder $q): Builder => $q->where('school_id', $this->selectedSchoolId));
            $events = AttendanceEvent::query()->whereIn('school_id', $schoolIds)->whereDate('attendance_date', $day)->when($this->selectedSchoolId, fn ($q) => $q->where('school_id', $this->selectedSchoolId));
            $approved = AttendanceLeaveRequest::query()->whereIn('school_id', $schoolIds)->where('status', AttendanceLeaveRequest::STATUS_APPROVED)->whereDate('start_date', '<=', $day)->whereDate('end_date', '>=', $day)->when($this->selectedSchoolId, fn (Builder $q): Builder => $q->where('school_id', $this->selectedSchoolId));
            $permitEmployees = (clone $base)->where('status', AttendanceRecord::STATUS_PERMIT)->pluck('employee_id')->merge((clone $approved)->whereIn('leave_type', [AttendanceLeaveRequest::TYPE_PERMIT, AttendanceLeaveRequest::TYPE_SICK])->pluck('employee_id'))->unique();
            $leaveEmployees = (clone $base)->where('status', AttendanceRecord::STATUS_LEAVE)->pluck('employee_id')->merge((clone $approved)->where('leave_type', AttendanceLeaveRequest::TYPE_LEAVE)->pluck('employee_id'))->unique();
            return ['date' => $day, 'label' => $date->locale('id')->translatedFormat('D, d M'), 'present' => (clone $base)->where('status', AttendanceRecord::STATUS_PRESENT)->distinct('employee_id')->count('employee_id'), 'late' => (clone $base)->where('status', AttendanceRecord::STATUS_LATE)->distinct('employee_id')->count('employee_id'), 'permit' => $permitEmployees->count(), 'leave' => $leaveEmployees->count(), 'rejected' => (clone $events)->where('event_status', 'rejected')->count(), 'fake_gps' => (clone $events)->where('is_mock_location', true)->count(), 'early' => (clone $base)->whereNotNull('check_out_reason')->distinct('employee_id')->count('employee_id')];
        })->values();

        return [
            'schoolOptions' => School::query()->whereKey($schoolIds)->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'metrics' => [
                ['label' => 'Pegawai Aktif', 'value' => $totalEmployees, 'class' => 'total'],
                ['label' => 'Presensi Tercatat', 'value' => $totalRecords, 'class' => 'recorded'],
                ['label' => 'Hadir', 'value' => $present, 'class' => 'present'],
                ['label' => 'Terlambat', 'value' => $late, 'class' => 'late'],
                ['label' => 'Izin', 'value' => $permit, 'class' => 'permit'],
                ['label' => 'Cuti', 'value' => $leave, 'class' => 'leave'],
                ['label' => 'Percobaan Ditolak', 'value' => $rejected, 'class' => 'rejected'],
                ['label' => 'Fake GPS', 'value' => $fakeGps, 'class' => 'fake-gps'],
                ['label' => 'Pulang Awal', 'value' => $early, 'class' => 'early'],
                ['label' => 'Persentase', 'value' => $totalEmployees > 0 ? number_format(($coveredEmployees->count() / $totalEmployees) * 100, 1).'%' : '0%', 'class' => 'percentage'],
            ],
            'sevenDays' => $sevenDays->map(fn (array $day): array => $day + [
                'height' => max(4, (int) round(($day['count'] / $maxDay) * 100)),
            ]),
            'dailyCharts' => $dailyCharts,
            'latestRecords' => AttendanceRecord::query()
                ->whereIn('school_id', $schoolIds)
                ->whereBetween('attendance_date', [$from, $to])
                ->when($this->selectedSchoolId, fn (Builder $query): Builder => $query->where('school_id', $this->selectedSchoolId))
                ->with(['employee', 'school'])
                ->latest('check_in_at')
                ->limit(8)
                ->get(),
            'statusOptions' => AttendanceRecord::statusOptions(),
            'mapSchools' => $mapSettings->map(fn (AttendanceSetting $setting): array => [
                'name' => $setting->school?->name ?? 'Sekolah/Madrasah',
                'latitude' => (float) $setting->office_latitude,
                'longitude' => (float) $setting->office_longitude,
                'radius' => $setting->radius_meters,
                'polygon' => $setting->geofence_polygon ?? [],
            ])->values(),
            'mapAttendances' => $mapRecords->map(fn (AttendanceRecord $record): array => [
                'name' => $record->employee?->name ?? '-',
                'type' => $record->employee?->employee_type === Employee::TYPE_GURU ? 'Guru' : 'Pegawai',
                'school' => $record->school?->name ?? '-',
                'latitude' => (float) $record->check_in_latitude,
                'longitude' => (float) $record->check_in_longitude,
                'checkIn' => $record->formattedStoredTime('check_in_at', 'H:i') ? $record->formattedStoredTime('check_in_at', 'H:i').' WIB' : null,
                'checkOut' => $record->formattedStoredTime('check_out_at', 'H:i') ? $record->formattedStoredTime('check_out_at', 'H:i').' WIB' : null,
                'status' => AttendanceRecord::statusOptions()[$record->status] ?? $record->status,
                'distance' => $record->check_in_distance !== null ? number_format((float) $record->check_in_distance, 0).' meter' : '-',
            ])->values(),
            'reportUrl' => AttendanceReport::getUrl(panel: $this->attendancePanelId(), isAbsolute: false),
            'settingsUrl' => AttendanceSettings::getUrl(panel: $this->attendancePanelId(), isAbsolute: false),
            'from' => $from,
            'to' => $to,
            'periodLabel' => $from === $to
                ? Carbon::parse($from)->locale('id')->translatedFormat('d F Y')
                : Carbon::parse($from)->locale('id')->translatedFormat('d M Y').' – '.Carbon::parse($to)->locale('id')->translatedFormat('d M Y'),
        ];
    }
}
