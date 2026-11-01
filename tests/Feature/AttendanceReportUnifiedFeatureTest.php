<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\AttendanceReport;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AttendanceReportUnifiedFeatureTest extends TestCase
{
    private User $adminInduk;

    private User $schoolAdmin;

    private User $teacher;

    private School $schoolOne;

    private School $schoolTwo;

    private Employee $teacherEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->seed(RolePermissionSeeder::class);

        $foundation = Foundation::factory()->create();
        $this->schoolOne = School::create([
            'foundation_id' => $foundation->id,
            'name' => 'GPS 20 Sekolah Satu',
            'npsn' => '92000001',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $this->schoolTwo = School::create([
            'foundation_id' => $foundation->id,
            'name' => 'GPS 20 Sekolah Dua',
            'npsn' => '92000002',
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        $this->adminInduk = User::factory()->create(['name' => 'GPS 20 Admin Induk']);
        $this->adminInduk->assignRole(User::ROLE_ADMIN_INDUK);

        $this->schoolAdmin = User::factory()->create(['name' => 'GPS 20 Admin Sekolah']);
        $this->schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::create([
            'user_id' => $this->schoolAdmin->id,
            'foundation_id' => $foundation->id,
            'school_id' => $this->schoolOne->id,
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create(['name' => 'GPS 20 Guru']);
        $this->teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        Membership::create([
            'user_id' => $this->teacher->id,
            'foundation_id' => $foundation->id,
            'school_id' => $this->schoolOne->id,
            'status' => 'active',
        ]);

        $this->teacherEmployee = Employee::factory()->create([
            'foundation_id' => $foundation->id,
            'school_id' => $this->schoolOne->id,
            'user_id' => $this->teacher->id,
            'name' => $this->teacher->name,
        ]);
    }

    private function resetDatabase(): void
    {
        $connection = DB::connection('sidikma_induk');
        $tables = $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'");

        $connection->statement('PRAGMA foreign_keys = OFF');
        foreach ($tables as $table) {
            $connection->table($table->name)->delete();
        }
        $connection->statement('PRAGMA foreign_keys = ON');
    }

    public function test_union_contains_success_and_rejected_rows_but_not_accepted_events(): void
    {
        $this->seedAttendanceRows();

        $records = $this->reportRecords($this->adminInduk);

        $this->assertCount(2, $records);
        $this->assertSame(['attendance_record', 'attendance_event'], $records->pluck('source_type')->all());
        $this->assertSame('rejected', $records->last()->is_rejected ? 'rejected' : $records->last()->status_key);
        $this->assertSame('Akurasi GPS gagal', $records->last()->rejection_reason);
    }

    public function test_rejected_filter_and_reason_filter_return_only_rejected_events(): void
    {
        $this->seedAttendanceRows();

        $records = $this->reportRecords($this->adminInduk, function (AttendanceReport $page): void {
            $page->selectedStatus = 'rejected';
            $page->selectedRejectionCode = 'invalid_accuracy';
        });

        $this->assertCount(1, $records);
        $this->assertTrue((bool) $records->first()->is_rejected);
        $this->assertSame('invalid_accuracy', $records->first()->rejection_code);
    }

    public function test_global_pagination_counts_union_rows_and_uses_twenty_five_rows(): void
    {
        $foundation = $this->schoolOne->foundation_id;

        foreach (range(1, 26) as $index) {
            $user = User::factory()->create(['name' => "GPS 20 Pegawai {$index}"]);
            $employee = Employee::factory()->create([
                'foundation_id' => $foundation,
                'school_id' => $this->schoolOne->id,
                'user_id' => $user->id,
            ]);
            AttendanceRecord::create([
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'school_id' => $this->schoolOne->id,
                'attendance_date' => '2026-09-10',
                'status' => AttendanceRecord::STATUS_PRESENT,
                'check_in_at' => Carbon::parse('2026-09-10 01:00:00')->addSeconds($index),
            ]);
        }

        AttendanceEvent::create([
            'user_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'school_id' => $this->schoolOne->id,
            'attendance_date' => '2026-09-10',
            'event_type' => 'check_in',
            'event_status' => 'rejected',
            'rejection_code' => 'outside_geofence',
            'rejection_reason' => 'Di luar area',
            'checked_at' => '2026-09-10 00:59:00',
        ]);

        $paginator = $this->reportPaginator($this->adminInduk);

        $this->assertSame(27, $paginator->total());
        $this->assertSame(25, $paginator->perPage());
        $this->assertCount(25, $paginator->items());
    }

    public function test_school_filter_and_unauthorized_school_are_enforced_on_report(): void
    {
        AttendanceRecord::create([
            'user_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'school_id' => $this->schoolOne->id,
            'attendance_date' => '2026-09-10',
            'status' => AttendanceRecord::STATUS_PRESENT,
        ]);

        $records = $this->reportRecords($this->adminInduk, function (AttendanceReport $page): void {
            $page->selectedSchoolId = $this->schoolOne->id;
        });
        $this->assertTrue($records->every(fn (object $record): bool => (int) $record->school_id === $this->schoolOne->id));

        $this->actingAs($this->schoolAdmin);
        $page = app(AttendanceReport::class);
        $page->selectedSchoolId = $this->schoolTwo->id;
        $method = new \ReflectionMethod($page, 'pageData');
        $method->setAccessible(true);

        $this->expectException(HttpException::class);
        $method->invoke($page);
    }

    public function test_admin_school_only_receives_its_school_rows_and_teacher_cannot_access(): void
    {
        AttendanceRecord::create([
            'user_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'school_id' => $this->schoolOne->id,
            'attendance_date' => '2026-09-10',
            'status' => AttendanceRecord::STATUS_PRESENT,
        ]);

        $records = $this->reportRecords($this->schoolAdmin);
        $this->assertTrue($records->every(fn (object $record): bool => (int) $record->school_id === $this->schoolOne->id));

        $this->actingAs($this->teacher);
        $this->assertFalse(AttendanceReport::canAccess());
    }

    public function test_filter_changes_reset_only_report_pagination(): void
    {
        $page = app(AttendanceReport::class);

        foreach ([
            'selectedSchoolId',
            'selectedEmployeeId',
            'selectedStatus',
            'selectedEventType',
            'selectedRejectionCode',
            'referenceDate',
            'dateFrom',
            'dateTo',
            'reportType',
        ] as $filter) {
            $page->setPage(4, 'reportPage');
            $this->assertSame(4, $page->getPage('reportPage'));

            $page->updated($filter);

            $this->assertSame(1, $page->getPage('reportPage'), "Filter {$filter} did not reset report pagination.");
        }

        $page->setPage(3, 'reportPage');
        $this->assertSame(3, $page->getPage('reportPage'));
    }

    public function test_export_is_blocked_for_rejected_status_or_reason_filter(): void
    {
        $page = app(AttendanceReport::class);
        $page->selectedStatus = 'rejected';
        $this->expectException(HttpException::class);
        $page->exportReport();
    }

    private function seedAttendanceRows(): void
    {
        AttendanceRecord::create([
            'user_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'school_id' => $this->schoolOne->id,
            'attendance_date' => '2026-09-10',
            'status' => AttendanceRecord::STATUS_PRESENT,
            'check_in_at' => '2026-09-10 01:05:00',
        ]);

        AttendanceEvent::create([
            'user_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'school_id' => $this->schoolOne->id,
            'attendance_date' => '2026-09-10',
            'event_type' => 'check_in',
            'event_status' => 'rejected',
            'rejection_code' => 'invalid_accuracy',
            'rejection_reason' => 'Akurasi GPS gagal',
            'checked_at' => '2026-09-10 01:00:00',
        ]);

        AttendanceEvent::create([
            'user_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'school_id' => $this->schoolOne->id,
            'attendance_date' => '2026-09-10',
            'event_type' => 'check_in',
            'event_status' => 'accepted',
            'checked_at' => '2026-09-10 01:05:00',
        ]);
    }

    private function reportRecords(User $user, ?callable $configure = null): \Illuminate\Support\Collection
    {
        $this->actingAs($user);
        $page = app(AttendanceReport::class);
        $page->dateFrom = '2026-09-01';
        $page->dateTo = '2026-09-30';
        $configure?->__invoke($page);

        $method = new \ReflectionMethod($page, 'unifiedReportRows');
        $method->setAccessible(true);

        return collect($method->invoke($page)->items());
    }

    private function reportPaginator(User $user): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->actingAs($user);
        $page = app(AttendanceReport::class);
        $page->dateFrom = '2026-09-01';
        $page->dateTo = '2026-09-30';

        $method = new \ReflectionMethod($page, 'unifiedReportRows');
        $method->setAccessible(true);

        return $method->invoke($page);
    }
}
