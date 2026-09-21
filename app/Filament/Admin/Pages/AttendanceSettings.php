<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ScopesAttendanceToAccessibleSchools;
use App\Models\AttendanceSetting;
use App\Models\School;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use UnitEnum;
use App\Services\AuditLogService;

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

    public string $checkOutStart = '14:00';

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
            'checkOutStart' => ['required', 'date_format:H:i', 'after_or_equal:12:00', 'after:lateAfter'],
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
            throw \Illuminate\Validation\ValidationException::withMessages([
                'polygonPoints' => 'Polygon harus kosong atau memiliki minimal 3 titik.',
            ]);
        }

        if ($polygonPoints->count() >= 3 && $polygonPoints
            ->map(fn (array $point): string => round((float) $point['latitude'], 7).':'.round((float) $point['longitude'], 7))
            ->unique()
            ->count() < 3) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'polygonPoints' => 'Polygon harus memiliki minimal 3 titik yang berbeda.',
            ]);
        }

        $before = AttendanceSetting::query()->where('school_id', $validated['selectedSchoolId'])->first()?->only(['check_in_start','late_after','check_out_start','office_latitude','office_longitude','radius_meters','maximum_accuracy_meters','geofence_polygon','require_location','detect_fake_gps','require_selfie','is_active','enable_check_in','enable_check_out']);
        $setting = AttendanceSetting::query()->updateOrCreate(
            ['school_id' => $validated['selectedSchoolId']],
            [
                'check_in_start' => $validated['checkInStart'].':00',
                'late_after' => $validated['lateAfter'].':00',
                'check_out_start' => $validated['checkOutStart'].':00',
                'office_latitude' => $validated['officeLatitude'],
                'office_longitude' => $validated['officeLongitude'],
                // The attendance boundary now follows the single GPS accuracy limit.
                'radius_meters' => $validated['maximumAccuracyMeters'],
                'maximum_accuracy_meters' => $validated['maximumAccuracyMeters'],
                'geofence_polygon' => $polygonPoints->count() >= 3 ? $polygonPoints->values()->all() : null,
                'require_location' => $validated['requireLocation'],
                'detect_fake_gps' => $validated['detectFakeGps'],
                'require_selfie' => $validated['requireSelfie'],
                'is_active' => $validated['isActive'],
                'enable_check_in' => $validated['enableCheckIn'],
                'enable_check_out' => $validated['enableCheckOut'],
                'updated_by' => auth()->id(),
            ],
        );
        $setting->increment('geofence_version');
        app(AuditLogService::class)->record('attendance_settings_updated', $setting, $setting->fresh()->only(['check_in_start','late_after','check_out_start','office_latitude','office_longitude','radius_meters','maximum_accuracy_meters','geofence_polygon','require_location','detect_fake_gps','require_selfie','is_active','enable_check_in','enable_check_out']), $before, ['school_id'=>$setting->school_id]);

        Notification::make()->success()->title('Pengaturan presensi disimpan')->send();
        $this->skipRender();
    }

    private function loadSettings(): void
    {
        if (! $this->selectedSchoolId) {
            return;
        }

        abort_unless($this->accessibleAttendanceSchoolIds()->contains($this->selectedSchoolId), 403);

        $setting = AttendanceSetting::forSchool($this->selectedSchoolId);
        $this->checkInStart = substr($setting->check_in_start, 0, 5);
        $checkIn = Carbon::createFromFormat('H:i:s', $setting->check_in_start);
        $this->checkInHour = $checkIn->format('h');
        $this->checkInMinute = str_pad((string) (intdiv((int) $checkIn->format('i'), 5) * 5), 2, '0', STR_PAD_LEFT);
        $this->lateAfter = substr($setting->late_after, 0, 5);
        $this->lateToleranceMinutes = (int) max(0, Carbon::createFromFormat('H:i:s', $setting->check_in_start)
            ->diffInMinutes(Carbon::createFromFormat('H:i:s', $setting->late_after), false));
        $this->checkOutStart = substr($setting->check_out_start, 0, 5);
        $checkOut = Carbon::createFromFormat('H:i:s', $setting->check_out_start);
        $this->checkOutHour = $checkOut->format('h');
        $this->checkOutMinute = str_pad((string) (intdiv((int) $checkOut->format('i'), 5) * 5), 2, '0', STR_PAD_LEFT);
        $this->officeLatitude = $setting->office_latitude;
        $this->officeLongitude = $setting->office_longitude;
        $this->radiusMeters = $setting->radius_meters;
        $this->maximumAccuracyMeters = $setting->maximum_accuracy_meters;
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
