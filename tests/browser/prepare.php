<?php

// Used only with the isolated SQLite database supplied by smoke.cjs.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || ! str_contains(config('database.connections.sqlite.database'), 'browser-smoke-')) {
    throw new RuntimeException('Browser tests require a dedicated SQLite database.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\RolePermissionSeeder::class, '--force' => true]);
$foundation = App\Models\Foundation::create(['name' => 'Browser Test', 'code' => 'BROWSER', 'is_active' => true]);
foreach (['A Browser School', 'B Browser School'] as $index => $name) {
    $school = App\Models\School::create(['foundation_id' => $foundation->id, 'name' => $name, 'npsn' => '9900900'.$index, 'school_level' => 'MI', 'is_active' => true]);
    App\Models\AttendanceSetting::forSchool($school->id);
}
$admin = App\Models\User::factory()->create(['email' => 'browser@example.test', 'password' => 'Browser-Test-123!']);
$admin->assignRole(App\Models\User::ROLE_ADMIN_INDUK);
$schoolAdmin = App\Models\User::factory()->create(['email' => 'school-browser@example.test', 'password' => 'Browser-Test-123!']);
$schoolAdmin->assignRole(App\Models\User::ROLE_ADMIN_SEKOLAH_MADRASAH);
App\Models\Membership::create(['user_id' => $schoolAdmin->id, 'foundation_id' => $foundation->id, 'school_id' => $school->id, 'status' => 'active']);
$teacher = App\Models\User::factory()->create(['email' => 'teacher-browser@example.test', 'password' => 'Browser-Test-123!']);
$teacher->assignRole(App\Models\User::ROLE_GURU_PEGAWAI);
App\Models\Membership::create(['user_id' => $teacher->id, 'foundation_id' => $foundation->id, 'school_id' => $school->id, 'status' => 'active']);
App\Models\Employee::factory()->create(['user_id' => $teacher->id, 'foundation_id' => $foundation->id, 'school_id' => $school->id, 'is_active' => true]);
App\Models\AttendanceSetting::where('school_id', $school->id)->update(['require_location' => true]);
echo "Browser fixture ready.\n";
App\Models\StudentEnrollment::create(['school_id' => $school->id, 'academic_year' => App\Models\StudentEnrollment::currentAcademicYear(),
    'k1' => 10, 'k2' => 13, 'k3' => 15, 'k4' => 15, 'k5' => 12, 'k6' => 9, 'k7' => 0, 'k8' => 0, 'k9' => 0, 'submitted_by' => $schoolAdmin->id]);
App\Models\EducatorRecap::create(['school_id' => $school->id, 'academic_year' => App\Models\EducatorRecap::currentAcademicYear(),
    'asn_certified' => 1, 'asn_uncertified' => 1, 'foundation_certified_inpassing' => 1, 'foundation_uncertified' => 1, 'submitted_by' => $schoolAdmin->id]);
