<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ScopesAttendanceToAccessibleSchools;
use App\Models\AttendanceSetting;
use App\Models\School;
use App\Services\AuditLogService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use UnitEnum;

class AttendanceSettings extends Page
{
    use ScopesAttendanceToAccessibleSchools;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Pengaturan Presensi';

    protected static ?string $slug = 'presensi/pengaturan';

    protected static bool $shouldRegisterNavigation = false;

    #[Url(as: 'school')]
    public ?int $selectedSchoolId = null;

    public string $checkInStart = '06:00';

    public string $checkInHour = '06';

    public string $checkInMinute = '00';

    public string $lateAfter = '07:15';

    public int $lateToleranceMinutes = 75;

    public string $checkInEnd = '11:59';

    public string $checkOutStart = '14:00';

    public string $checkOutEnd = '17:00';

    public string $checkOutHour = '02';

    public string $checkOutMinute = '00';


    public ?string $officeLatitude = null;

    public ?string $officeLongitude = null;

    public int $radiusMeters = 100;

    public int $maximumAccuracyMeters = 100;

    /** @var array<int, array{latitude: float, longitude: float}> */
    public array $polygonPoints = [];

    public bool $requireLocation = false;

    public bool $detectFakeGps = false;

    public bool $requireSelfie = false;

    public bool $isActive = true;

    public bool $enableCheckIn = true;

    public bool $enableCheckOut = true;

    public function mount(): void
    {
        $this->selectedSchoolId ??= School::query()->whereKey($this->accessibleAttendanceSchoolIds())->where('is_active', true)->orderBy('name')->value('id');
        $this->loadSettings();
    }

    public static function canAccess(): bool
    {
        return static::canUseAttendanceManagement() && (auth()->user()?->can('attendance.settings') ?? false);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.pages.attendance-settings')
                ->viewData(fn (): array => [
                    'schoolOptions' => School::query()->whereKey($this->accessibleAttendanceSchoolIds())->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
                    'dashboardUrl' => AttendanceDashboard::getUrl(panel: $this->attendancePanelId(), isAbsolute: false),
                ]),
        ]);
    }

    public function updatedSelectedSchoolId(): void
    {
        abort_if($this->selectedSchoolId && ! $this->accessibleAttendanceSchoolIds()->contains($this->selectedSchoolId), 403);
        $this->loadSettings();
    }

    public function saveSettings(): void
    {
        $this->isActive = $this->enableCheckIn || $this->enableCheckOut;
        $this->checkInStart = ($this->checkInHour === '12' ? '00' : $this->checkInHour).':'.$this->checkInMinute;
        $this->checkOutStart = ($this->checkOutHour === '12' ? '12' : str_pad((string) ((int) $this->checkOutHour + 12), 2, '0', STR_PAD_LEFT)).':'.$this->checkOutMinute;
        $this->checkOutEnd = $this->checkOutEnd ?: '17:00';
        $this->lateAfter = Carbon::createFromFormat('H:i', $this->checkInStart)
            ->addMinutes($this->lateToleranceMinutes)
            ->format('H:i');

        $validated = $this->validate([
            'selectedSchoolId' => ['required', 'integer', Rule::in($this->accessibleAttendanceSchoolIds())],
            'checkInStart' => ['required', 'date_format:H:i', 'before:12:00'],
            'checkInHour' => ['required', Rule::in(['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'])],
            'checkInMinute' => ['required', Rule::in(['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'])],
            'lateAfter' => ['required', 'date_format:H:i', 'after_or_equal:checkInStart'],
            'lateToleranceMinutes' => ['required', 'integer', 'min:0', 'max:720'],
            'checkInEnd' => ['required', 'date_format:H:i', 'after:lateAfter', 'before_or_equal:checkOutStart'],
            'checkOutStart' => ['required', 'date_format:H:i', 'after_or_equal:12:00', 'after:lateAfter'],
            'checkOutEnd' => ['required', 'date_format:H:i', 'after:checkOutStart'],
            'checkOutHour' => ['required', Rule::in(['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'])],
            'checkOutMinute' => ['required', Rule::in(['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'])],
            'officeLatitude' => ['required_if:requireLocation,true', 'required_with:officeLongitude', 'nullable', 'numeric', 'between:-90,90'],
            'officeLongitude' => ['required_if:requireLocation,true', 'required_with:officeLatitude', 'nullable', 'numeric', 'between:-180,180'],
            'maximumAccuracyMeters' => ['required', 'integer', 'min:10', 'max:1000'],
            'polygonPoints' => ['array'],
            'polygonPoints.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'polygonPoints.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'requireLocation' => ['boolean'],
            'detectFakeGps' => ['boolean'],
            'requireSelfie' => ['boolean'],
            'isActive' => ['boolean'],
            'enableCheckIn' => ['boolean'],
            'enableCheckOut' => ['boolean'],
        ]);

        $polygonPoints = collect($validated['polygonPoints']);
        if ($polygonPoints->count() > 0 && $polygonPoints->count() < 3) {
            throw ValidationException::withMessages([
                'polygonPoints' => 'Polygon harus kosong atau memiliki minimal 3 titik.',
            ]);
        }

        if ($polygonPoints->count() >= 3 && $polygonPoints
            ->map(fn (array $point): string => round((float) $point['latitude'], 7).':'.round((float) $point['longitude'], 7))
            ->unique()
            ->count() < 3) {
            throw ValidationException::withMessages([
                'polygonPoints' => 'Polygon harus memiliki minimal 3 titik yang berbeda.',
            ]);
        }

        $before = AttendanceSetting::query()->where('school_id', $validated['selectedSchoolId'])->first()?->only(['check_in_start','late_after','check_out_start','check_out_end','office_latitude','office_longitude','radius_meters','maximum_accuracy_meters','geofence_polygon','require_location','detect_fake_gps','require_selfie','is_active','enable_check_in','enable_check_out']);
        $canChangeFeatureActivation = auth()->user()?->isAdminInduk() ?? false;
        $setting = AttendanceSetting::query()->updateOrCreate(
            ['school_id' => $validated['selectedSchoolId']],
            [
                'check_in_start' => $validated['checkInStart'].':00',
                'late_after' => $validated['lateAfter'].':00',
                'check_in_end' => $validated['checkInEnd'].':00',
                'check_out_start' => $validated['checkOutStart'].':00',
                'check_out_end' => $validated['checkOutEnd'].':00',
                'office_latitude' => $validated['officeLatitude'],
                'office_longitude' => $validated['officeLongitude'],
                // The attendance boundary now follows the single GPS accuracy limit.
                'radius_meters' => $validated['maximumAccuracyMeters'],
                'maximum_accuracy_meters' => $validated['maximumAccuracyMeters'],
                'geofence_polygon' => $polygonPoints->count() >= 3 ? $polygonPoints->values()->all() : null,
                'require_location' => $canChangeFeatureActivation ? $validated['requireLocation'] : (bool) ($before['require_location'] ?? false),
                'detect_fake_gps' => $canChangeFeatureActivation ? $validated['detectFakeGps'] : (bool) ($before['detect_fake_gps'] ?? false),
                'require_selfie' => $canChangeFeatureActivation ? $validated['requireSelfie'] : (bool) ($before['require_selfie'] ?? false),
                'is_active' => $canChangeFeatureActivation ? $validated['isActive'] : (bool) ($before['is_active'] ?? true),
                'enable_check_in' => $canChangeFeatureActivation ? $validated['enableCheckIn'] : (bool) ($before['enable_check_in'] ?? true),
                'enable_check_out' => $canChangeFeatureActivation ? $validated['enableCheckOut'] : (bool) ($before['enable_check_out'] ?? true),
                'updated_by' => auth()->id(),
            ],
        );
        $setting->increment('geofence_version');
        app(AuditLogService::class)->record('attendance_settings_updated', $setting, $setting->fresh()->only(['check_in_start','late_after','check_out_start','check_out_end','office_latitude','office_longitude','radius_meters','maximum_accuracy_meters','geofence_polygon','require_location','detect_fake_gps','require_selfie','is_active','enable_check_in','enable_check_out']), $before, ['school_id'=>$setting->school_id]);

        Notification::make()->success()->title('Pengaturan presensi disimpan')->send();
        $this->skipRender();
    }

    public function applyTimeSettingsToAllSchools(): void
    {
        abort_unless(auth()->user()?->isAdminInduk(), 403);

        $this->checkInStart = ($this->checkInHour === '12' ? '00' : $this->checkInHour).':'.$this->checkInMinute;
        $this->checkOutStart = ($this->checkOutHour === '12' ? '12' : str_pad((string) ((int) $this->checkOutHour + 12), 2, '0', STR_PAD_LEFT)).':'.$this->checkOutMinute;
        $validated = $this->validate([
            'checkInStart' => ['required', 'date_format:H:i', 'before:12:00'],
            'checkInHour' => ['required', Rule::in(['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'])],
            'checkInMinute' => ['required', Rule::in(['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'])],
            'lateAfter' => ['required', 'date_format:H:i', 'after_or_equal:checkInStart'],
            'checkInEnd' => ['required', 'date_format:H:i', 'after:lateAfter', 'before_or_equal:checkOutStart'],
            'checkOutStart' => ['required', 'date_format:H:i', 'after_or_equal:12:00', 'after:checkInEnd'],
            'checkOutEnd' => ['required', 'date_format:H:i', 'after:checkOutStart'],
            'checkOutHour' => ['required', Rule::in(['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'])],
            'checkOutMinute' => ['required', Rule::in(['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'])],
        ]);

        $schoolIds = School::query()->where('is_active', true)->pluck('id');
        $timeSettings = [
            'check_in_start' => $validated['checkInStart'].':00',
            'late_after' => $validated['lateAfter'].':00',
            'check_in_end' => $validated['checkInEnd'].':00',
            'check_out_start' => $validated['checkOutStart'].':00',
            'check_out_end' => $validated['checkOutEnd'].':00',
            'updated_by' => auth()->id(),
        ];

        DB::connection('sidikma_induk')->transaction(function () use ($schoolIds, $timeSettings): void {
            foreach ($schoolIds as $schoolId) {
                AttendanceSetting::query()->updateOrCreate(['school_id' => $schoolId], $timeSettings);
            }
        });

        app(AuditLogService::class)->record('attendance_time_settings_applied_to_all_schools', AttendanceSetting::class, [
            ...$timeSettings,
            'school_count' => $schoolIds->count(),
        ]);

        Notification::make()->success()->title("Pengaturan waktu diterapkan ke {$schoolIds->count()} sekolah")->send();
        $this->skipRender();
    }

    private function loadSettings(): void
    {
        if (! $this->selectedSchoolId) {
            return;
        }

        abort_unless($this->accessibleAttendanceSchoolIds()->contains($this->selectedSchoolId), 403);

        $setting = AttendanceSetting::forSchool($this->selectedSchoolId);
        $checkInStart = $setting->check_in_start ?: '06:00:00';
        $lateAfter = $setting->late_after ?: '07:15:00';
        $checkOutStart = $setting->check_out_start ?: '14:00:00';
        $this->checkInStart = substr($checkInStart, 0, 5);
        $checkIn = Carbon::createFromFormat('H:i:s', $checkInStart);
        $this->checkInHour = $checkIn->format('h');
        $this->checkInMinute = str_pad((string) (intdiv((int) $checkIn->format('i'), 5) * 5), 2, '0', STR_PAD_LEFT);
        $this->lateAfter = substr($lateAfter, 0, 5);
        $this->lateToleranceMinutes = max(0, Carbon::createFromFormat('H:i:s', $checkInStart)
            ->diffInMinutes(Carbon::createFromFormat('H:i:s', $lateAfter)));
        $this->checkInEnd = $setting->check_in_end
            ? substr($setting->check_in_end, 0, 5)
            : Carbon::createFromFormat('H:i:s', $checkOutStart)->subMinute()->format('H:i');
        $this->checkOutStart = substr($checkOutStart, 0, 5);
        $checkOut = Carbon::createFromFormat('H:i:s', $checkOutStart);
        $this->checkOutHour = $checkOut->format('h');
        $this->checkOutMinute = str_pad((string) (intdiv((int) $checkOut->format('i'), 5) * 5), 2, '0', STR_PAD_LEFT);
        $this->checkOutEnd = substr($setting->check_out_end ?: '17:00:00', 0, 5);
        $this->officeLatitude = $setting->office_latitude;
        $this->officeLongitude = $setting->office_longitude;
        $this->radiusMeters = (int) ($setting->radius_meters ?: 100);
        $this->maximumAccuracyMeters = (int) ($setting->maximum_accuracy_meters ?: $this->radiusMeters);
        $this->polygonPoints = $setting->geofence_polygon ?? [];
        $this->requireLocation = $setting->require_location;
        $this->detectFakeGps = $setting->detect_fake_gps;
        $this->requireSelfie = $setting->require_selfie;
        $this->isActive = $setting->is_active;
        $this->enableCheckIn = $setting->enable_check_in;
        $this->enableCheckOut = $setting->enable_check_out;
        $this->resetValidation();
    }
}
