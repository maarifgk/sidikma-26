<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\AttendanceDashboard;
use App\Filament\Admin\Pages\AttendanceLeaveRequests;
use App\Filament\Admin\Pages\AttendanceReport;
use App\Filament\Admin\Pages\AttendanceSettings;
use App\Filament\App\Pages\MyAttendance;
use App\Filament\App\Pages\MyAttendanceLeaveRequests;
use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceEvent;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use App\Services\GeocodingService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private School $school;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake(AttendanceRecord::DISK);

        $foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
        $this->school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI Presensi Terpadu',
            'npsn' => '99887766',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $this->admin = User::factory()->create(['name' => 'Admin Induk Presensi']);
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->teacher = User::factory()->create(['name' => 'Guru Presensi Terpadu']);
        $this->teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        Membership::query()->create([
            'user_id' => $this->teacher->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        $this->employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'user_id' => $this->teacher->getKey(),
            'employee_code' => 'EWANUGK-PRESENSI-01',
            'name' => $this->teacher->name,
            'is_active' => true,
        ]);
        AttendanceSetting::query()->create([
            'school_id' => $this->school->getKey(),
            'check_in_start' => '06:00:00',
            'late_after' => '07:15:00',
            'check_out_start' => '14:00:00',
            'radius_meters' => 200,
            'require_location' => false,
            'require_selfie' => false,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_teacher_check_in_is_immediately_visible_on_parent_dashboard_and_report(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 00:30:00', 'UTC'));
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);

        $teacherAttendance = Livewire::test(MyAttendance::class)
            ->assertSee('Presensi Saya')
            ->call('checkIn')
            ->assertHasNoErrors();

        $teacherAttendance
            ->assertSee('Terlambat');

        Carbon::setTestNow(Carbon::parse('2026-08-11 07:00:00', 'UTC'));
        $teacherAttendance
            ->call('checkOut')
            ->assertHasNoErrors();

        $record = AttendanceRecord::query()->sole();
        $this->assertSame(AttendanceRecord::STATUS_LATE, $record->status);
        $this->assertSame($this->school->getKey(), $record->school_id);
        $this->assertNotNull($record->fresh()->check_out_at);

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        Livewire::test(AttendanceDashboard::class)
            ->assertSee('Dashboard Presensi')
            ->assertSee($this->teacher->name)
            ->assertSee('TERLAMBAT');

        Livewire::test(AttendanceReport::class)
            ->set('dateFrom', '2026-08-11')
            ->set('dateTo', '2026-08-11')
            ->assertSee($this->teacher->name)
            ->assertSee($this->school->name);
    }

    public function test_invalid_coordinates_are_recorded_as_rejected_event(): void
    {
        AttendanceSetting::forSchool($this->school->id)->update(['require_location' => true, 'office_latitude' => -7.9, 'office_longitude' => 110.6]);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);
        Livewire::test(MyAttendance::class)->call('checkIn')->assertHasErrors(['latitude']);
        $event = AttendanceEvent::query()->sole();
        $this->assertSame('rejected', $event->event_status);
        $this->assertSame('invalid_coordinates', $event->rejection_code);
        $this->assertNotNull($event->rejection_reason);
        $this->assertNull($event->latitude);
        $this->assertSame($this->employee->id, $event->employee_id);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_required_selfie_is_recorded_as_rejected_event_without_file(): void
    {
        AttendanceSetting::forSchool($this->school->id)->update(['require_selfie' => true]);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);
        Livewire::test(MyAttendance::class)->call('checkIn')->assertHasErrors(['selfie']);
        $event = AttendanceEvent::query()->sole();
        $this->assertSame('selfie_required', $event->rejection_code);
        $this->assertNull($event->selfie_path);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_changed_geofence_version_is_rejected_with_conflict(): void
    {
        $setting = AttendanceSetting::forSchool($this->school->id);
        $setting->update(['geofence_version' => 7]);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);
        $component = Livewire::test(MyAttendance::class)->set('clientGeofenceVersion', 6)->call('checkIn');
        $reflection = new \ReflectionClass($component);
        $state = $reflection->getProperty('lastState');
        $state->setAccessible(true);
        $this->assertSame(409, $state->getValue($component)->getResponse()->status());
        $this->assertSame('location_settings_changed', AttendanceEvent::query()->sole()->rejection_code);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_school_admin_can_access_attendance_management_in_app_panel(): void
    {
        $schoolAdmin = User::factory()->create(['name' => 'Admin Madrasah Presensi']);
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $this->school->foundation_id,
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        filament()->setCurrentPanel(filament()->getPanel('app'));

        foreach ([AttendanceDashboard::class, AttendanceReport::class, AttendanceLeaveRequests::class, AttendanceSettings::class] as $page) {
            $this->actingAs($schoolAdmin)
                ->get($page::getUrl(panel: 'app', isAbsolute: false))
                ->assertOk();
        }

        Livewire::test(AttendanceSettings::class)
            ->assertSet('selectedSchoolId', $this->school->getKey())
            ->set('checkInHour', '07')
            ->set('checkInMinute', '00')
            ->set('checkOutHour', '03')
            ->set('checkOutMinute', '30')
            ->set('lateToleranceMinutes', 20)
            ->set('maximumAccuracyMeters', 60)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_settings', [
            'school_id' => $this->school->getKey(),
            'check_in_start' => '07:00:00',
            'late_after' => '07:20:00',
            'check_out_start' => '15:30:00',
            'maximum_accuracy_meters' => 60,
        ]);
    }

    public function test_parent_admin_can_search_attendance_location(): void
    {
        $geocoding = $this->createMock(GeocodingService::class);
        $geocoding->expects($this->once())->method('search')->with('MI Maarif Sawahan')->willReturn([[
            'display_name' => 'MI Maarif Sawahan, Gunungkidul',
            'latitude' => -7.965321,
            'longitude' => 110.603214,
            'type' => 'school',
        ]]);
        $this->app->instance(GeocodingService::class, $geocoding);

        $this->actingAs($this->admin)
            ->getJson(route('attendance.location-search', ['q' => 'MI Maarif Sawahan']))
            ->assertOk()
            ->assertJsonPath('results.0.type', 'school');
    }

    public function test_any_active_user_linked_to_an_employee_can_use_personal_attendance(): void
    {
        $schoolAdmin = User::factory()->create(['name' => 'Admin Sekolah Merangkap Pegawai']);
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $this->school->foundation_id,
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        Employee::factory()->create([
            'foundation_id' => $this->school->foundation_id,
            'school_id' => $this->school->getKey(),
            'user_id' => $schoolAdmin->getKey(),
            'employee_code' => 'ADMIN-PRESENSI-01',
            'name' => $schoolAdmin->name,
            'is_active' => true,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-11 00:00:00', 'UTC'));
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($schoolAdmin);

        Livewire::test(MyAttendance::class)
            ->assertSee('Presensi Saya')
            ->call('checkIn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $schoolAdmin->getKey(),
            'employee_id' => $schoolAdmin->employee->getKey(),
        ]);
    }

    public function test_teacher_leave_request_can_be_approved_and_becomes_attendance_records(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);

        Livewire::test(MyAttendanceLeaveRequests::class)
            ->set('leaveType', AttendanceLeaveRequest::TYPE_PERMIT)
            ->set('startDate', '2026-08-12')
            ->set('endDate', '2026-08-13')
            ->set('reason', 'Mengikuti kegiatan keluarga di luar kota')
            ->call('submitRequest')
            ->assertHasNoErrors()
            ->assertSee('Menunggu');

        $request = AttendanceLeaveRequest::query()->sole();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        Livewire::test(AttendanceLeaveRequests::class)
            ->assertSee($this->teacher->name)
            ->set("reviewNotes.{$request->getKey()}", 'Disetujui admin induk')
            ->call('approve', $request->getKey())
            ->assertHasNoErrors()
            ->assertSee('Disetujui');

        $this->assertDatabaseHas('attendance_leave_requests', [
            'id' => $request->getKey(),
            'status' => AttendanceLeaveRequest::STATUS_APPROVED,
            'reviewed_by' => $this->admin->getKey(),
        ]);
        $this->assertSame(2, AttendanceRecord::query()->where('status', AttendanceRecord::STATUS_PERMIT)->count());
    }

    public function test_parent_admin_can_save_school_attendance_settings(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        Livewire::test(AttendanceSettings::class)
            ->set('selectedSchoolId', $this->school->getKey())
            ->set('checkInHour', '06')
            ->set('checkInMinute', '30')
            ->set('lateToleranceMinutes', 30)
            ->set('checkOutHour', '01')
            ->set('checkOutMinute', '30')
            ->set('officeLatitude', '-7.9767346')
            ->set('officeLongitude', '110.6157939')
            ->set('maximumAccuracyMeters', 75)
            ->set('radiusMeters', 125)
            ->set('requireLocation', true)
            ->set('detectFakeGps', true)
            ->set('enableCheckIn', true)
            ->set('enableCheckOut', false)
            ->set('requireSelfie', true)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_settings', [
            'school_id' => $this->school->getKey(),
            'late_after' => '07:00:00',
            'radius_meters' => 125,
            'maximum_accuracy_meters' => 75,
            'require_location' => true,
            'detect_fake_gps' => true,
            'enable_check_in' => true,
            'enable_check_out' => false,
            'require_selfie' => true,
        ]);
    }

    public function test_attendance_location_without_polygon_survives_page_reload(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        Livewire::test(AttendanceSettings::class)
            ->set('selectedSchoolId', $this->school->getKey())
            ->set('officeLatitude', '-7.9767346')
            ->set('officeLongitude', '110.6157939')
            ->set('polygonPoints', [])
            ->set('radiusMeters', 350)
            ->set('requireLocation', true)
            ->call('saveSettings')
            ->assertHasNoErrors();

        Livewire::test(AttendanceSettings::class)
            ->assertSet('selectedSchoolId', $this->school->getKey())
            ->assertSet('officeLatitude', '-7.9767346')
            ->assertSet('officeLongitude', '110.6157939')
            ->assertSet('polygonPoints', [])
            ->assertSet('radiusMeters', 350)
            ->assertSet('requireLocation', true);
    }

    public function test_attendance_polygon_requires_three_unique_points_and_saves_every_point(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        Livewire::test(AttendanceSettings::class)
            ->set('selectedSchoolId', $this->school->getKey())
            ->set('polygonPoints', [
                ['latitude' => -7.9769, 'longitude' => 110.6156],
                ['latitude' => -7.9770, 'longitude' => 110.6160],
            ])
            ->call('saveSettings')
            ->assertHasErrors(['polygonPoints']);

        $points = [
            ['latitude' => -7.9769, 'longitude' => 110.6156],
            ['latitude' => -7.9770, 'longitude' => 110.6160],
            ['latitude' => -7.9771, 'longitude' => 110.6154],
            ['latitude' => -7.9768, 'longitude' => 110.6152],
        ];

        Livewire::test(AttendanceSettings::class)
            ->set('selectedSchoolId', $this->school->getKey())
            ->set('polygonPoints', $points)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertCount(4, AttendanceSetting::forSchool($this->school->getKey())->geofence_polygon);
    }

    public function test_selected_school_is_restored_from_url_and_saved_location_is_used_for_attendance(): void
    {
        $otherSchool = School::query()->create([
            'foundation_id' => $this->school->foundation_id,
            'name' => 'ZZ Sekolah Kedua', 'npsn' => '99112233', 'school_level' => 'MI', 'is_active' => true,
        ]);
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);
        Livewire::withQueryParams(['school' => $otherSchool->id])->test(AttendanceSettings::class)
            ->assertSet('selectedSchoolId', $otherSchool->id)
            ->set('officeLatitude', '-7.9000000')->set('officeLongitude', '110.6000000')
            ->set('radiusMeters', 300)->call('saveSettings')->assertHasNoErrors();
        Livewire::withQueryParams(['school' => $otherSchool->id])->test(AttendanceSettings::class)
            ->assertSet('selectedSchoolId', $otherSchool->id)
            ->assertSet('officeLatitude', '-7.9000000')->assertSet('radiusMeters', 300);
        $this->assertNull(AttendanceSetting::forSchool($this->school->id)->office_latitude);

        Livewire::withQueryParams(['school' => $this->school->id])->test(AttendanceSettings::class)
            ->set('officeLatitude', '-7.9767346')->set('officeLongitude', '110.6157939')
            ->set('radiusMeters', 350)->set('requireLocation', true)
            ->call('saveSettings')->assertHasNoErrors();
        $this->assertDatabaseHas('attendance_settings', [
            'school_id' => $this->school->id, 'office_latitude' => -7.9767346,
            'office_longitude' => 110.6157939, 'radius_meters' => 350,
        ]);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);
        Livewire::withQueryParams([])->test(MyAttendance::class)
            ->set('latitude', '-7.9767346')->set('longitude', '110.6157939')
            ->set('accuracy', 10)->call('checkIn')->assertHasNoErrors();
        $this->assertDatabaseHas('attendance_records', ['employee_id' => $this->employee->id]);
    }

    public function test_teacher_cannot_open_parent_attendance_dashboard(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->actingAs($this->teacher)
            ->get(AttendanceDashboard::getUrl(panel: 'admin', isAbsolute: false))
            ->assertForbidden();
    }

    public function test_teacher_gps_must_be_accurate_and_can_retry_after_a_better_fix(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-07 03:00:00', 'UTC'));
        AttendanceSetting::forSchool($this->school->id)->update([
            'require_location' => true, 'office_latitude' => -7.9768, 'office_longitude' => 110.6158,
            'maximum_accuracy_meters' => 100,
        ]);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);
        $page = Livewire::test(MyAttendance::class)
            ->set('latitude', -7.9768)->set('longitude', 110.6158)
            ->call('checkIn')->assertHasErrors(['accuracy'])
            ->set('accuracy', 800)->call('checkIn')->assertHasErrors(['attendance']);
        $this->assertDatabaseCount('attendance_records', 0);
        $page->set('accuracy', 10)->call('checkIn')->assertHasNoErrors();
        $this->assertDatabaseHas('attendance_records', ['employee_id' => $this->employee->id, 'check_in_accuracy' => 10]);
    }

    public function test_school_admin_cannot_load_another_schools_location_from_url(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::create(['user_id' => $schoolAdmin->id, 'foundation_id' => $this->school->foundation_id,
            'school_id' => $this->school->id, 'status' => 'active']);
        $this->actingAs($schoolAdmin);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->get(AttendanceSettings::getUrl(panel: 'app', isAbsolute: false).'?school=999999')->assertForbidden();
    }

    public function test_configured_gps_accuracy_allows_check_in_and_out_but_still_checks_school_polygon(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 05:00:00', 'UTC'));
        $setting = AttendanceSetting::forSchool($this->school->id);
        $setting->update([
            'require_location' => true,
            'office_latitude' => -7.800,
            'office_longitude' => 110.360,
            'maximum_accuracy_meters' => 25,
            'geofence_polygon' => [
                ['latitude' => -7.801, 'longitude' => 110.359],
                ['latitude' => -7.799, 'longitude' => 110.359],
                ['latitude' => -7.799, 'longitude' => 110.361],
                ['latitude' => -7.801, 'longitude' => 110.361],
            ],
        ]);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->teacher);
        $page = Livewire::test(MyAttendance::class)
            ->set('latitude', -7.800)->set('longitude', 110.360)->set('accuracy', 124)
            ->call('checkIn')->assertHasErrors(['attendance']);
        $this->assertDatabaseCount('attendance_records', 0);
        $setting->update(['maximum_accuracy_meters' => 150]);
        $page->set('latitude', -7.810)->call('checkIn')->assertHasErrors(['attendance']);
        $this->assertDatabaseCount('attendance_records', 0);
        $page->set('latitude', -7.800)->call('checkIn')->assertHasNoErrors();
        Carbon::setTestNow(Carbon::parse('2026-09-08 07:00:00', 'UTC'));
        $page->call('checkOut')->assertHasNoErrors();
        $record = AttendanceRecord::query()->sole();
        $this->assertNotNull($record->check_out_at);
        $this->assertEquals(124, $record->check_in_accuracy);
        $this->assertEquals(124, $record->check_out_accuracy);
    }

    public function test_attendance_selfie_is_private_to_authorized_accounts(): void
    {
        $path = 'selfies/test/masuk.jpg';
        Storage::disk(AttendanceRecord::DISK)->put($path, 'foto presensi');
        $record = AttendanceRecord::query()->create([
            'user_id' => $this->teacher->getKey(),
            'employee_id' => $this->employee->getKey(),
            'school_id' => $this->school->getKey(),
            'attendance_date' => '2026-08-11',
            'status' => AttendanceRecord::STATUS_PRESENT,
            'check_in_at' => now(),
            'check_in_selfie_path' => $path,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('attendance.selfie.view', [$record, 'masuk']))
            ->assertOk();

        $unrelatedTeacher = User::factory()->create();
        $unrelatedTeacher->assignRole(User::ROLE_GURU_PEGAWAI);

        $this->actingAs($unrelatedTeacher)
            ->get(route('attendance.selfie.view', [$record, 'masuk']))
            ->assertForbidden();
    }
}
