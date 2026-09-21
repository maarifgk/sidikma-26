<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\AttendanceDashboard;
use App\Models\AttendanceEvent;
use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $one;
    private School $two;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $foundation = Foundation::factory()->create();
        $this->one = School::create(['foundation_id' => $foundation->id, 'name' => 'Dashboard Satu', 'npsn' => '11111111', 'school_level' => 'MI', 'is_active' => true]);
        $this->two = School::create(['foundation_id' => $foundation->id, 'name' => 'Dashboard Dua', 'npsn' => '22222222', 'school_level' => 'MI', 'is_active' => true]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->employee = Employee::factory()->create(['foundation_id' => $foundation->id, 'school_id' => $this->one->id, 'user_id' => $this->admin->id]);
    }

    private function dashboardComponent(): mixed
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        return Livewire::test(AttendanceDashboard::class)->set('dateFrom', '2026-09-01')->set('dateTo', '2026-09-03');
    }

    private function dashboardData(string $from = '2026-09-01', string $to = '2026-09-03'): array
    {
        $page = app(AttendanceDashboard::class);
        $page->dateFrom = $from;
        $page->dateTo = $to;
        $method = new \ReflectionMethod($page, 'pageData');
        $method->setAccessible(true);
        return $method->invoke($page);
    }

    public function test_statistics_are_unique_and_rejected_events_are_not_present(): void
    {
        $this->actingAs($this->admin);
        AttendanceRecord::create(['user_id' => $this->admin->id, 'employee_id' => $this->employee->id, 'school_id' => $this->one->id, 'attendance_date' => '2026-09-01', 'status' => 'late', 'check_in_at' => '2026-09-01 01:00:00', 'check_out_at' => '2026-09-01 07:00:00', 'check_out_reason' => 'Keperluan dinas']);
        AttendanceEvent::create(['user_id' => $this->admin->id, 'employee_id' => $this->employee->id, 'school_id' => $this->one->id, 'attendance_date' => '2026-09-03', 'event_type' => 'check_in', 'event_status' => 'rejected', 'rejection_code' => 'fake_gps', 'rejection_reason' => 'Lokasi palsu', 'is_mock_location' => true, 'checked_at' => '2026-09-03 01:00:00']);
        $data = $this->dashboardData();
        $metrics = collect($data['metrics'])->keyBy('label');
        $this->assertSame(1, $metrics['Total User Aktif']['value']);
        $this->assertSame(1, $metrics['Terlambat']['value']);
        $this->assertSame(1, $metrics['Ditolak']['value']);
        $this->assertSame(1, $metrics['Fake GPS']['value']);
        $this->assertSame(1, $metrics['Pulang Awal']['value']);
        $this->assertSame('100.0%', $metrics['Persentase']['value']);
        $this->assertCount(3, $data['dailyCharts']);
        $this->assertSame(0, $data['dailyCharts'][1]['present']);
    }

    public function test_dashboard_filters_school_and_rejects_unauthorized_school(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::create(['user_id' => $schoolAdmin->id, 'foundation_id' => $this->one->foundation_id, 'school_id' => $this->one->id, 'status' => 'active']);
        $this->actingAs($schoolAdmin);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $page = app(AttendanceDashboard::class);
        $page->selectedSchoolId = $this->two->id;
        $method = new \ReflectionMethod($page, 'pageData');
        $method->setAccessible(true);
        $method->invoke($page);
    }

    public function test_regular_user_cannot_access_dashboard(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertFalse(AttendanceDashboard::canAccess());
    }
}
