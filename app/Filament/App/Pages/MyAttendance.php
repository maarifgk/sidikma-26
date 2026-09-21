<?php

namespace App\Filament\App\Pages;

use App\Models\AttendanceRecord;
use App\Models\AttendanceEvent;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Services\AttendanceLocationService;
use App\Services\AttendanceEventService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use UnitEnum;

class MyAttendance extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Presensi Saya';

    protected static ?string $slug = 'presensi-saya';

    protected static bool $shouldRegisterNavigation = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?float $accuracy = null;

    public mixed $selfie = null;
    public string $checkOutReason = '';
    public bool $fakeGps = false;
    public ?string $fakeGpsSource = null;
    public ?int $clientGeofenceVersion = null;
    private string $activeEventType = 'check_in';

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
            View::make('filament.app.pages.my-attendance-wrapper')
                ->viewData(fn (): array => $this->pageData()),
        ]);
    }

    public function checkIn(): void
    {
        $this->activeEventType = 'check_in';
        [$employee, $setting] = $this->context();
        $this->guardGeofenceVersion($employee, $setting, 'check_in');

        if (! $setting->enable_check_in) {
            $this->rejectCurrent('check_in', 'feature_disabled', 'Presensi masuk sedang dinonaktifkan.', $setting->geofence_version);
            throw ValidationException::withMessages(['attendance' => 'Presensi masuk sedang dinonaktifkan.']);
        }

        $distance = $this->validateAttendanceRequirements($setting);
        $localNow = now(config('attendance.timezone'));

        if ($localNow->format('H:i:s') < $setting->check_in_start) {
            throw ValidationException::withMessages(['attendance' => 'Presensi masuk belum dibuka.']);
        }

        $date = $localNow->toDateString();
        if ($setting->detect_fake_gps && $this->fakeGps) {
            DB::transaction(fn () => $this->recordRejectedAttempt($employee, $date, 'check_in', 'fake_gps', 'Presensi ditolak karena perangkat melaporkan lokasi palsu/mock.', $this->fakeGpsSource, $setting->geofence_version));
            throw ValidationException::withMessages(['attendance' => 'Presensi ditolak: lokasi palsu/mock terdeteksi.']);
        }
        try {
            DB::transaction(function () use ($employee, $setting, $date, $localNow, $distance): void {
                // Lock the parent row too: a missing attendance row cannot itself be locked.
                Employee::query()->whereKey($employee->getKey())->lockForUpdate()->firstOrFail();
                $existing = AttendanceRecord::query()->where('employee_id', $employee->getKey())->whereDate('attendance_date', $date)->lockForUpdate()->first();
                if ($existing) {
                    app(AttendanceEventService::class)->record($employee, 'check_in', 'rejected', $this->rejectionData('duplicate_attendance', 'Presensi hari ini sudah tercatat.', $setting->geofence_version, $date));
                    throw ValidationException::withMessages(['attendance' => 'Presensi hari ini sudah tercatat.']);
                }
                $record = AttendanceRecord::query()->create([
            'user_id' => auth()->id(),
            'employee_id' => $employee->getKey(),
            'school_id' => $employee->school_id,
            'attendance_date' => $date,
            'status' => $localNow->format('H:i:s') > $setting->late_after ? AttendanceRecord::STATUS_LATE : AttendanceRecord::STATUS_PRESENT,
            'check_in_at' => now(),
            'check_in_latitude' => $this->latitude,
            'check_in_longitude' => $this->longitude,
            'check_in_accuracy' => $this->accuracy,
            'check_in_distance' => $distance,
            'check_in_selfie_path' => $this->storeSelfie($employee, 'masuk', $date),
            'check_in_fake_gps' => $this->fakeGps, 'check_in_fake_gps_source' => $this->fakeGpsSource, 'check_in_geofence_version' => $setting->geofence_version,
                ]);
                app(AttendanceEventService::class)->accepted($employee, $record, 'check_in', ['latitude'=>$this->latitude,'longitude'=>$this->longitude,'gps_accuracy'=>$this->accuracy,'is_inside_geofence'=>$setting->require_location ? true : null,'is_mock_location'=>$this->fakeGps,'mock_detection_source'=>$this->fakeGpsSource,'selfie_path'=>$record->check_in_selfie_path,'geofence_version'=>$setting->geofence_version]);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                $this->rejectCurrent('check_in', 'duplicate_attendance', 'Presensi hari ini sudah tercatat akibat permintaan bersamaan.', $setting->geofence_version, $date);
                throw ValidationException::withMessages(['attendance' => 'Presensi hari ini sudah tercatat. Silakan muat ulang halaman.']);
            }
            throw $exception;
        }

        $this->reset('selfie');
        $this->dispatch('$refresh');
        Notification::make()->success()->title('Presensi masuk berhasil')->send();
    }

    public function checkOut(): void
    {
        $this->activeEventType = 'check_out';
        [$employee, $setting] = $this->context();
        $this->guardGeofenceVersion($employee, $setting, 'check_out');

        if (! $setting->enable_check_out) {
            $this->rejectCurrent('check_out', 'feature_disabled', 'Presensi pulang sedang dinonaktifkan.', $setting->geofence_version);
            throw ValidationException::withMessages(['attendance' => 'Presensi pulang sedang dinonaktifkan.']);
        }

        $distance = $this->validateAttendanceRequirements($setting);
        $localNow = now(config('attendance.timezone'));

        if ($localNow->format('H:i:s') < $setting->check_out_start) {
            throw ValidationException::withMessages(['attendance' => 'Presensi pulang belum dibuka.']);
        }

        if ($setting->detect_fake_gps && $this->fakeGps) {
            DB::transaction(fn () => $this->recordRejectedAttempt($employee, $localNow->toDateString(), 'check_out', 'fake_gps', 'Presensi pulang ditolak karena perangkat melaporkan lokasi palsu/mock.', $this->fakeGpsSource, $setting->geofence_version));
            throw ValidationException::withMessages(['attendance' => 'Presensi pulang ditolak: lokasi palsu/mock terdeteksi.']);
        }

        DB::transaction(function () use ($employee, $setting, $distance, $localNow): void {
            Employee::query()->whereKey($employee->getKey())->lockForUpdate()->firstOrFail();
            $record = AttendanceRecord::query()
                ->where('employee_id', $employee->getKey())
                ->whereDate('attendance_date', $localNow->toDateString())
                ->whereIn('status', [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_LATE])
                ->lockForUpdate()->first();
            if (! $record || $record->check_out_at) {
                throw ValidationException::withMessages(['attendance' => 'Data presensi masuk tidak ditemukan atau presensi pulang sudah dilakukan.']);
            }
            if ($localNow->format('H:i:s') < $setting->check_out_start && blank($this->checkOutReason)) {
                app(AttendanceEventService::class)->record($employee, 'check_out', 'rejected', $this->rejectionData('early_checkout_reason_required', 'Alasan wajib diisi jika pulang lebih awal.', $setting->geofence_version, $localNow->toDateString()));
                throw ValidationException::withMessages(['checkOutReason' => 'Alasan wajib diisi jika pulang lebih awal.']);
            }
            $record->update([
            'check_out_at' => now(),
            'check_out_latitude' => $this->latitude,
            'check_out_longitude' => $this->longitude,
            'check_out_accuracy' => $this->accuracy,
            'check_out_distance' => $distance,
            'check_out_selfie_path' => $this->storeSelfie($employee, 'pulang', $localNow->toDateString()),
            'check_out_reason' => filled($this->checkOutReason) ? $this->checkOutReason : null, 'check_out_fake_gps' => $this->fakeGps, 'check_out_fake_gps_source' => $this->fakeGpsSource, 'check_out_geofence_version' => $setting->geofence_version,
            ]);
            app(AttendanceEventService::class)->accepted($employee, $record, 'check_out', ['latitude'=>$this->latitude,'longitude'=>$this->longitude,'gps_accuracy'=>$this->accuracy,'is_inside_geofence'=>$setting->require_location ? true : null,'is_mock_location'=>$this->fakeGps,'mock_detection_source'=>$this->fakeGpsSource,'selfie_path'=>$record->check_out_selfie_path,'geofence_version'=>$setting->geofence_version]);
        });

        $this->reset('selfie');
        $this->dispatch('$refresh');
        Notification::make()->success()->title('Presensi pulang berhasil')->send();
    }

    /** @return array<string, mixed> */
    private function pageData(): array
    {
        $employee = Employee::query()->with('school')->where('user_id', auth()->id())->where('is_active', true)->first();
        $today = now(config('attendance.timezone'))->toDateString();
        $record = $employee ? AttendanceRecord::query()->where('employee_id', $employee->getKey())->whereDate('attendance_date', $today)->first() : null;
        $setting = $employee?->school_id ? AttendanceSetting::forSchool($employee->school_id) : null;

        return [
            'employee' => $employee,
            'todayRecord' => $record,
            'setting' => $setting,
            'statusOptions' => AttendanceRecord::statusOptions(),
            'recentRecords' => $employee ? AttendanceRecord::query()->where('employee_id', $employee->getKey())->latest('attendance_date')->limit(10)->get() : collect(),
            'leaveUrl' => MyAttendanceLeaveRequests::getUrl(panel: 'app', isAbsolute: false),
            'todayLabel' => now(config('attendance.timezone'))->locale('id')->translatedFormat('l, d F Y'),
        ];
    }

    /** @return array{0: Employee, 1: AttendanceSetting} */
    private function context(): array
    {
        $employee = Employee::query()->where('user_id', auth()->id())->where('is_active', true)->first();

        if (! $employee || ! $employee->school_id) {
            throw ValidationException::withMessages(['attendance' => 'Akun belum terhubung dengan data guru/pegawai dan sekolah.']);
        }

        $setting = AttendanceSetting::forSchool($employee->school_id);

        if (! $setting->is_active) {
            throw ValidationException::withMessages(['attendance' => 'Presensi sekolah sedang dinonaktifkan.']);
        }

        return [$employee, $setting];
    }

    private function validateAttendanceRequirements(AttendanceSetting $setting): ?float
    {
        $rules = [];

        if ($setting->require_location) {
            $rules['latitude'] = ['required', 'numeric', 'between:-90,90'];
            $rules['longitude'] = ['required', 'numeric', 'between:-180,180'];
            $rules['accuracy'] = ['required', 'numeric', 'min:0'];
        }

        if ($setting->require_selfie) {
            $rules['selfie'] = ['required', 'image', 'max:5120'];
        } elseif ($this->selfie) {
            $rules['selfie'] = ['image', 'max:5120'];
        }

        if ($setting->require_location && ($this->latitude === null || $this->longitude === null || ! is_numeric($this->latitude) || ! is_numeric($this->longitude))) {
            $this->rejectCurrent($this->currentEventType(), 'invalid_coordinates', 'Koordinat GPS tidak valid atau belum tersedia.', $setting->geofence_version);
        }
        if ($setting->require_selfie && ! $this->selfie) {
            $this->rejectCurrent($this->currentEventType(), 'selfie_required', 'Selfie wajib diunggah sebelum presensi.', $setting->geofence_version);
        }
        if ($rules !== []) {
            $this->validate($rules, [
                'latitude.required' => 'Aktifkan lokasi GPS terlebih dahulu.',
                'accuracy.required' => 'Akurasi GPS belum terbaca. Ambil ulang lokasi sebelum presensi.',
                'selfie.required' => 'Selfie wajib diunggah.',
            ]);
        }

        if ($setting->require_location) {
            $locationService = app(AttendanceLocationService::class);

            $accuracyLimit = max((float) $setting->maximum_accuracy_meters, (float) config('attendance.minimum_accuracy_tolerance_meters', 100));
            if ($this->accuracy !== null && ! $locationService->hasAcceptableAccuracy($this->accuracy, $accuracyLimit)) {
                $this->rejectCurrent($this->currentEventType(), 'invalid_accuracy', 'Akurasi GPS belum cukup untuk memvalidasi lokasi.', $setting->geofence_version);
                throw ValidationException::withMessages(['attendance' => 'Akurasi GPS ±'.ceil($this->accuracy).' m; batas sekolah '.$setting->maximum_accuracy_meters.' m. Ambil ulang lokasi di area terbuka atau gunakan ponsel dengan GPS aktif.']);
            }

            if (blank($setting->office_latitude) || blank($setting->office_longitude)) {
                throw ValidationException::withMessages(['attendance' => 'Koordinat lokasi sekolah belum diatur oleh admin.']);
            }

            $distance = $locationService->calculateDistance(
                (float) $setting->office_latitude,
                (float) $setting->office_longitude,
                (float) $this->latitude,
                (float) $this->longitude,
            );

            if (count($setting->geofence_polygon ?? []) < 3) {
                $this->rejectCurrent($this->currentEventType(), 'feature_disabled', 'Polygon geofence sekolah belum dikonfigurasi.', $setting->geofence_version);
                throw ValidationException::withMessages(['attendance' => 'Polygon lokasi sekolah belum dikonfigurasi. Hubungi admin sekolah.']);
            }
            $insideArea = $locationService->isInsidePolygon((float) $this->latitude, (float) $this->longitude, $setting->geofence_polygon);

            if (! $insideArea) {
                $this->rejectCurrent($this->currentEventType(), 'outside_geofence', 'Anda berada di luar area presensi.', $setting->geofence_version);
                throw ValidationException::withMessages(['attendance' => 'Anda berada di luar area presensi.']);
            }

            return round($distance, 2);
        }

        return null;
    }

    private function storeSelfie(Employee $employee, string $type, string $date): ?string
    {
        if (! $this->selfie) {
            return null;
        }

        return $this->selfie->store("selfies/{$employee->school_id}/{$date}/{$type}", AttendanceRecord::DISK);
    }

    private function recordRejectedAttempt(Employee $employee, string $date, string $type, string $code, string $reason, ?string $source = null, ?int $version = null): void
    {
        app(AttendanceEventService::class)->record($employee, $type, 'rejected', ['attendance_date'=>$date,'rejection_code'=>$code,'rejection_reason'=>$reason,'is_mock_location'=>$this->fakeGps,'mock_detection_source'=>$source,'latitude'=>$this->latitude,'longitude'=>$this->longitude,'gps_accuracy'=>$this->accuracy,'geofence_version'=>$version]);
    }
    private function currentEventType(): string { return $this->activeEventType; }
    private function rejectionData(string $code, string $reason, ?int $version, ?string $date = null): array { return ['attendance_date'=>$date ?? now(config('attendance.timezone'))->toDateString(),'rejection_code'=>$code,'rejection_reason'=>$reason,'is_mock_location'=>$this->fakeGps,'mock_detection_source'=>$this->fakeGpsSource,'latitude'=>$this->latitude,'longitude'=>$this->longitude,'gps_accuracy'=>$this->accuracy,'geofence_version'=>$version]; }
    private function rejectCurrent(string $type, string $code, string $reason, ?int $version = null, ?string $date = null): void { [$employee] = $this->context(); DB::transaction(fn () => app(AttendanceEventService::class)->record($employee, $type, 'rejected', $this->rejectionData($code, $reason, $version, $date))); }
    private function guardGeofenceVersion(Employee $employee, AttendanceSetting $setting, string $type): void
    {
        if ($this->clientGeofenceVersion !== null && $this->clientGeofenceVersion !== (int) $setting->geofence_version) {
            DB::transaction(fn () => app(AttendanceEventService::class)->record($employee, $type, 'rejected', $this->rejectionData('location_settings_changed', 'Konfigurasi lokasi sekolah telah berubah. Ambil ulang lokasi.', $setting->geofence_version) + ['client_geofence_version'=>$this->clientGeofenceVersion]));
            throw new HttpException(409, 'Konfigurasi lokasi berubah. Ambil ulang lokasi sebelum presensi.');
        }
    }
}
