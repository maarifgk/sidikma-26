<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ScopesAttendanceToAccessibleSchools;
use App\Models\AttendanceRecord;
use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Models\School;
use App\Models\AttendanceSetting;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AttendanceXlsxExportService;
use App\Models\AttendanceLeaveRequest;
use UnitEnum;
use Livewire\WithPagination;

class AttendanceReport extends Page
{
    use ScopesAttendanceToAccessibleSchools;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Laporan Presensi';

    protected static ?string $slug = 'presensi/laporan';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $selectedSchoolId = null;

    public ?int $selectedEmployeeId = null;

    public string $selectedStatus = '';

    public string $selectedEventType = '';

    public string $selectedRejectionCode = '';

    public string $reportType = 'custom';

    public string $referenceDate = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $today = now('Asia/Jakarta');
        $this->referenceDate = $today->toDateString();
        $this->dateFrom = $today->startOfMonth()->toDateString();
        $this->dateTo = now('Asia/Jakarta')->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'selectedSchoolId',
            'selectedEmployeeId',
            'selectedStatus',
            'selectedEventType',
            'selectedRejectionCode',
            'referenceDate',
            'dateFrom',
            'dateTo',
            'reportType',
        ], true)) {
            $this->resetPage('reportPage');
        }
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
            View::make('filament.admin.pages.attendance-report')
                ->viewData(fn (): array => $this->pageData()),
        ]);
    }

    public function exportReport(): StreamedResponse
    {
        abort_if($this->selectedStatus === 'rejected' || filled($this->selectedRejectionCode), 422, 'Export Kehadiran tidak memuat percobaan presensi ditolak.');

        $records = $this->reportRecords();
        [$from, $to] = $this->range();
        $leaves = AttendanceLeaveRequest::query()->whereIn('school_id', $this->accessibleAttendanceSchoolIds())->whereDate('start_date','<=',$to)->whereDate('end_date','>=',$from)->with(['employee','school','reviewedBy'])->get();
        $headers = ['Tanggal','Nama','Employee ID','Sekolah','Status','Jam Datang','Jam Pulang','Akurasi Datang','Akurasi Pulang','Lokasi Datang','Lokasi Pulang','Geofence Datang','Geofence Pulang','Mode Geofence','Jarak Datang (m)','Jarak Pulang (m)','Fake GPS Datang','Fake GPS Pulang','Sumber Fake GPS','Alasan Pulang Awal','Rejection Code','Rejection Reason','Keterangan'];
        $events = \App\Models\AttendanceEvent::query()->whereIn('school_id', $this->accessibleAttendanceSchoolIds())->whereNotNull('attendance_id')->get()->groupBy('attendance_id');
        $attendance = [$headers]; foreach($records as $r){$ev=$events->get($r->id, collect());$in=$ev->firstWhere('event_type','check_in');$out=$ev->firstWhere('event_type','check_out');$mode=AttendanceSetting::forSchool($r->school_id)->geofence_polygon ? 'polygon':'radius';$attendance[]=[Carbon::parse($r->attendance_date)->format('d-m-Y'),$r->employee?->name,$r->employee_id,$r->school?->name,AttendanceRecord::statusOptions()[$r->status]??$r->status,$r->formattedStoredTime('check_in_at'),$r->formattedStoredTime('check_out_at'),$r->check_in_accuracy,$r->check_out_accuracy,$this->coordinates($r->check_in_latitude,$r->check_in_longitude),$this->coordinates($r->check_out_latitude,$r->check_out_longitude),$in?->is_inside_geofence,$out?->is_inside_geofence,$mode,$r->check_in_distance,$r->check_out_distance,$r->check_in_fake_gps?'Ya':'Tidak',$r->check_out_fake_gps?'Ya':'Tidak',$r->check_in_fake_gps_source?:$r->check_out_fake_gps_source,$r->check_out_reason,$in?->rejection_code?:$out?->rejection_code,$in?->rejection_reason?:$out?->rejection_reason,$r->notes];}
        $leaveRows = [['Nama','Employee ID','Sekolah','Kategori','Tanggal Mulai','Tanggal Selesai','Alasan','Status','Reviewer','Catatan Review','Waktu Pengajuan','Waktu Review']]; foreach($leaves as $l){$leaveRows[]=[ $l->employee?->name,$l->employee_id,$l->school?->name,$l->leave_type,$l->start_date?->format('d-m-Y'),$l->end_date?->format('d-m-Y'),$l->reason,$l->status,$l->reviewedBy?->name,$l->review_notes,$l->created_at?->timezone(config('attendance.timezone'))->format('d-m-Y H:i:s'),$l->reviewed_at?->timezone(config('attendance.timezone'))->format('d-m-Y H:i:s')];}
        return app(AttendanceXlsxExportService::class)->download($attendance,$leaveRows,'laporan-presensi-'.$from.'-'.$to.'.xlsx');

        return response()->streamDownload(function () use ($records): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Sekolah', 'Nama', 'Status', 'Jam Masuk', 'Jam Pulang', 'Lokasi Masuk', 'Lokasi Pulang', 'Keterangan'], ';');

            foreach ($records as $record) {
                fputcsv($output, [
                    $record->attendance_date?->format('d-m-Y'),
                    $record->school?->name,
                    $record->employee?->name,
                    AttendanceRecord::statusOptions()[$record->status] ?? $record->status,
                    $record->formattedStoredTime('check_in_at'),
                    $record->formattedStoredTime('check_out_at'),
                    $this->coordinates($record->check_in_latitude, $record->check_in_longitude),
                    $this->coordinates($record->check_out_latitude, $record->check_out_longitude),
                    $record->notes,
                ], ';');
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportReportType(string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['daily', 'weekly', 'monthly'], true), 404);

        $previousReportType = $this->reportType;
        $this->reportType = $type;
        $response = $this->exportReport();
        $this->reportType = $previousReportType;

        return $response;
    }

    /** @return array<string, mixed> */
    private function pageData(): array
    {
        [$from, $to] = $this->range();

        return [
            'schoolOptions' => School::query()->whereKey($this->accessibleAttendanceSchoolIds())->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'employeeOptions' => Employee::query()->whereIn('school_id', $this->accessibleAttendanceSchoolIds())->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'statusOptions' => array_merge(AttendanceRecord::statusOptions(), ['rejected' => 'Ditolak']),
            'selectedStatus' => $this->selectedStatus,
            'selectedRejectionCode' => $this->selectedRejectionCode,
            'records' => $this->unifiedReportRows(),
            'reportType' => $this->reportType,
            'fromLabel' => Carbon::parse($from)->format('d-m-Y'),
            'toLabel' => Carbon::parse($to)->format('d-m-Y'),
            'dashboardUrl' => AttendanceDashboard::getUrl(panel: $this->attendancePanelId(), isAbsolute: false),
            'rejectionLabels' => $this->rejectionLabels(),
            'rejectionCodeOptions' => $this->rejectionCodeOptions(),
        ];
    }

    private function unifiedReportRows(): LengthAwarePaginator
    {
        [$from, $to] = $this->range();
        $schoolIds = $this->accessibleAttendanceSchoolIds();

        abort_if($this->selectedSchoolId && ! $schoolIds->contains($this->selectedSchoolId), 403);

        $records = AttendanceRecord::query()
            ->from('attendance_records as ar')
            ->leftJoin('employees as employee', 'employee.id', '=', 'ar.employee_id')
            ->leftJoin('schools as school', 'school.id', '=', 'ar.school_id')
            ->whereIn('ar.school_id', $schoolIds)
            ->whereBetween('ar.attendance_date', [$from, $to])
            ->when($this->selectedSchoolId, fn (Builder $query): Builder => $query->where('ar.school_id', $this->selectedSchoolId))
            ->when($this->selectedEmployeeId, fn (Builder $query): Builder => $query->where('ar.employee_id', $this->selectedEmployeeId))
            ->when(filled($this->selectedStatus) && $this->selectedStatus !== 'rejected', fn (Builder $query): Builder => $query->where('ar.status', $this->selectedStatus))
            ->when($this->selectedStatus === 'rejected', fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
            ->when($this->selectedEventType === 'check_in', fn (Builder $query): Builder => $query->whereNotNull('ar.check_in_at'))
            ->when($this->selectedEventType === 'check_out', fn (Builder $query): Builder => $query->whereNotNull('ar.check_out_at'))
            ->when(filled($this->selectedRejectionCode), fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
            ->select([
                'ar.id as source_id',
                DB::raw("'attendance_record' as source_type"),
                'ar.attendance_date',
                DB::raw('COALESCE(ar.check_out_at, ar.check_in_at) as occurred_at'),
                'ar.school_id',
                'ar.employee_id',
                'employee.name as employee_name',
                'school.name as school_name',
                'ar.status as status_key',
                DB::raw('0 as is_rejected'),
                DB::raw('NULL as rejection_code'),
                DB::raw('NULL as rejection_reason'),
                DB::raw('NULL as event_type'),
                'ar.check_in_at',
                'ar.check_out_at',
                'ar.check_in_latitude',
                'ar.check_in_longitude',
                'ar.check_in_accuracy',
                'ar.check_out_latitude',
                'ar.check_out_longitude',
                'ar.check_out_accuracy',
                'ar.check_in_reason',
                'ar.check_out_reason',
                'ar.notes',
                'ar.check_in_selfie_path',
                'ar.check_out_selfie_path',
            ]);

        $rejected = DB::connection('sidikma_induk')
            ->table('attendance_events as ae')
            ->leftJoin('employees as employee', 'employee.id', '=', 'ae.employee_id')
            ->leftJoin('schools as school', 'school.id', '=', 'ae.school_id')
            ->whereIn('ae.school_id', $schoolIds)
            ->where('ae.event_status', 'rejected')
            ->whereBetween('ae.attendance_date', [$from, $to])
            ->when($this->selectedSchoolId, fn ($query) => $query->where('ae.school_id', $this->selectedSchoolId))
            ->when($this->selectedEmployeeId, fn ($query) => $query->where('ae.employee_id', $this->selectedEmployeeId))
            ->when($this->selectedStatus !== '' && $this->selectedStatus !== 'rejected', fn ($query) => $query->whereRaw('1 = 0'))
            ->when(filled($this->selectedEventType), fn ($query) => $query->where('ae.event_type', $this->selectedEventType))
            ->when(filled($this->selectedRejectionCode), fn ($query) => $query->where('ae.rejection_code', $this->selectedRejectionCode))
            ->select([
                'ae.id as source_id',
                DB::raw("'attendance_event' as source_type"),
                'ae.attendance_date',
                DB::raw('COALESCE(ae.checked_at, ae.created_at) as occurred_at'),
                'ae.school_id',
                'ae.employee_id',
                'employee.name as employee_name',
                'school.name as school_name',
                DB::raw('NULL as status_key'),
                DB::raw('1 as is_rejected'),
                'ae.rejection_code',
                'ae.rejection_reason',
                'ae.event_type',
                DB::raw('NULL as check_in_at'),
                DB::raw('NULL as check_out_at'),
                DB::raw("CASE WHEN ae.event_type = 'check_in' THEN ae.latitude ELSE NULL END as check_in_latitude"),
                DB::raw("CASE WHEN ae.event_type = 'check_in' THEN ae.longitude ELSE NULL END as check_in_longitude"),
                DB::raw("CASE WHEN ae.event_type = 'check_in' THEN ae.gps_accuracy ELSE NULL END as check_in_accuracy"),
                DB::raw("CASE WHEN ae.event_type = 'check_out' THEN ae.latitude ELSE NULL END as check_out_latitude"),
                DB::raw("CASE WHEN ae.event_type = 'check_out' THEN ae.longitude ELSE NULL END as check_out_longitude"),
                DB::raw("CASE WHEN ae.event_type = 'check_out' THEN ae.gps_accuracy ELSE NULL END as check_out_accuracy"),
                DB::raw('NULL as check_in_reason'),
                DB::raw('NULL as check_out_reason'),
                DB::raw('NULL as notes'),
                DB::raw('NULL as check_in_selfie_path'),
                DB::raw('NULL as check_out_selfie_path'),
            ]);

        return DB::connection('sidikma_induk')
            ->query()
            ->fromSub($records->unionAll($rejected), 'report_rows')
            ->orderByDesc('occurred_at')
            ->orderByDesc('source_type')
            ->orderByDesc('source_id')
            ->paginate(25, ['*'], 'reportPage');
    }

    /** @return array<string, string> */
    private function rejectionLabels(): array
    {
        return [
            'invalid_accuracy' => 'Akurasi GPS tidak memenuhi batas.',
            'outside_geofence' => 'Lokasi berada di luar area sekolah.',
            'fake_gps' => 'Lokasi palsu/mock terdeteksi.',
            'duplicate_attendance' => 'Presensi pada hari ini sudah tercatat.',
            'invalid_coordinates' => 'Koordinat GPS tidak valid atau belum tersedia.',
            'selfie_required' => 'Selfie wajib belum tersedia.',
            'location_settings_changed' => 'Konfigurasi lokasi sekolah telah berubah.',
            'early_checkout_reason_required' => 'Alasan pulang awal belum diisi.',
            'feature_disabled' => 'Fitur presensi sedang dinonaktifkan.',
        ];
    }

    /** @return array<string, string> */
    private function rejectionCodeOptions(): array
    {
        return AttendanceEvent::query()
            ->whereIn('school_id', $this->accessibleAttendanceSchoolIds())
            ->where('event_status', 'rejected')
            ->whereNotNull('rejection_code')
            ->distinct()
            ->orderBy('rejection_code')
            ->pluck('rejection_code', 'rejection_code')
            ->all();
    }

    private function reportRecords(): Collection
    {
        [$from, $to] = $this->range();

        abort_if($this->selectedSchoolId && ! $this->accessibleAttendanceSchoolIds()->contains($this->selectedSchoolId), 403);

        return AttendanceRecord::query()->whereIn('school_id', $this->accessibleAttendanceSchoolIds())
            ->whereBetween('attendance_date', [$from, $to])
            ->when($this->selectedSchoolId, fn (Builder $query): Builder => $query->where('school_id', $this->selectedSchoolId))
            ->when($this->selectedEmployeeId, fn (Builder $query): Builder => $query->where('employee_id', $this->selectedEmployeeId))
            ->when(filled($this->selectedStatus), fn (Builder $query): Builder => $query->where('status', $this->selectedStatus))
            ->with(['employee', 'school'])
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_at')
            ->limit(500)
            ->get();
    }

    /** @return array{0: string, 1: string} */
    private function range(): array
    {
        $reference = Carbon::parse($this->referenceDate ?: now('Asia/Jakarta')->toDateString());

        return match ($this->reportType) {
            'daily' => [$reference->toDateString(), $reference->toDateString()],
            'weekly' => [$reference->copy()->startOfWeek()->toDateString(), $reference->copy()->endOfWeek()->toDateString()],
            'monthly' => [$reference->copy()->startOfMonth()->toDateString(), $reference->copy()->endOfMonth()->toDateString()],
            default => [$this->dateFrom ?: $reference->toDateString(), $this->dateTo ?: $reference->toDateString()],
        };
    }

    private function coordinates(mixed $latitude, mixed $longitude): string
    {
        return filled($latitude) && filled($longitude) ? "{$latitude}, {$longitude}" : '-';
    }
}
