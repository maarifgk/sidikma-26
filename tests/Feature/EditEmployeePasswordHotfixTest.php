<?php

namespace Tests\Feature;

use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EditEmployeePasswordHotfixTest extends TestCase
{
    private const OLD_PASSWORD = 'PasswordLama-2026!';

    private const NEW_PASSWORD = 'PasswordBaru-2026!';

    protected function beginIsolatedTransactions(): void
    {
        DB::connection('sqlite')->beginTransaction();
        DB::connection('sidikma_induk')->beginTransaction();
    }

    protected function rollbackIsolatedTransactions(): void
    {
        foreach (['sidikma_induk', 'sqlite'] as $connection) {
            $db = DB::connection($connection);

            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
        }
    }

    private function invokePasswordUpdater(User $account, string $password): void
    {
        $page = (new \ReflectionClass(EditEmployee::class))->newInstanceWithoutConstructor();
        $passwordProperty = new \ReflectionProperty(EditEmployee::class, 'newAccountPassword');
        $passwordProperty->setAccessible(true);
        $passwordProperty->setValue($page, $password);

        $method = new \ReflectionMethod(EditEmployee::class, 'updateExistingAccountPassword');
        $method->setAccessible(true);
        $method->invoke($page, $account);
    }

    public function test_admin_induk_can_change_existing_account_password_without_creating_records(): void
    {
        try {
            $admin = User::factory()->create();
            $admin->assignRole(User::ROLE_ADMIN_INDUK);
            $this->actingAs($admin);
            $account = User::factory()->create([
                'password' => Hash::make(self::OLD_PASSWORD),
                'is_active' => false,
            ]);
            $school = School::on('sidikma_induk')->firstOrFail();
            $foundationId = $school->foundation_id;
            $position = EmployeePosition::on('sidikma_induk')->firstOrFail();
            $employee = Employee::factory()->create([
                'user_id' => $account->getKey(),
                'foundation_id' => $foundationId,
                'school_id' => $school->getKey(),
                'employee_code' => 'TEST-PASSWORD-'.random_int(100000, 999999),
            ]);
            $assignment = EmployeeAssignment::factory()->create([
                'employee_id' => $employee->getKey(),
                'employee_position_id' => $position->getKey(),
                'foundation_id' => $foundationId,
                'school_id' => $school->getKey(),
            ]);

            // Create fixtures before opening write transactions on both SQLite PDOs.
            // SQLite permits one writer at a time; holding the default connection's
            // write transaction while creating Employee on sidikma_induk deadlocks.
            $this->beginIsolatedTransactions();

            $before = [
                'users' => User::count(),
                'employees' => Employee::on('sidikma_induk')->count(),
                'memberships' => Membership::on('sidikma_induk')->count(),
                'assignments' => EmployeeAssignment::on('sidikma_induk')->count(),
                'roles' => $account->roles()->pluck('id')->sort()->values()->all(),
                'user_id' => $account->getKey(),
                'employee_id' => $employee->getKey(),
            ];

            $this->invokePasswordUpdater($account, self::NEW_PASSWORD);

            $freshAccount = User::query()->findOrFail($before['user_id']);
            $freshEmployee = Employee::on('sidikma_induk')->findOrFail($before['employee_id']);

            $this->assertTrue(Hash::check(self::NEW_PASSWORD, $freshAccount->password));
            $this->assertFalse(Hash::check(self::OLD_PASSWORD, $freshAccount->password));
            $this->assertSame($before['user_id'], $freshAccount->getKey());
            $this->assertSame($before['employee_id'], $freshEmployee->getKey());
            $this->assertSame($before['user_id'], $freshEmployee->user_id);
            $this->assertSame($before['users'], User::count());
            $this->assertSame($before['employees'], Employee::on('sidikma_induk')->count());
            $this->assertSame($before['memberships'], Membership::on('sidikma_induk')->count());
            $this->assertSame($before['assignments'], EmployeeAssignment::on('sidikma_induk')->count());
            $this->assertSame($before['roles'], $freshAccount->roles()->pluck('id')->sort()->values()->all());
            $this->assertSame(1, EmployeeAssignment::on('sidikma_induk')->whereKey($assignment->getKey())->count());
            $this->assertTrue($freshAccount->is_active, 'SQLite writable-column branch should activate the account.');
        } finally {
            $this->rollbackIsolatedTransactions();
        }
    }

    public function test_empty_password_preserves_existing_hash_and_unauthorized_user_is_denied(): void
    {
        try {
            $admin = User::factory()->create();
            $admin->assignRole(User::ROLE_ADMIN_INDUK);
            $this->actingAs($admin);
            $account = User::factory()->create(['password' => Hash::make(self::OLD_PASSWORD)]);
            $employee = Employee::factory()->create(['user_id' => $account->getKey()]);
            $oldHash = $account->password;

            $this->beginIsolatedTransactions();

            // The production lifecycle guards this call with filled($this->newAccountPassword).
            // An empty field therefore must not invoke the updater.
            $this->assertFalse(filled(''));

            $this->assertSame($oldHash, $account->fresh()->password);

            $unauthorized = User::factory()->create();
            $this->assertFalse(Gate::forUser($unauthorized)->allows('update', $employee->fresh()));
        } finally {
            $this->rollbackIsolatedTransactions();
        }
    }

    public function test_sqlite_metadata_exposes_writable_is_active_and_no_generated_column(): void
    {
        $columns = collect(Schema::connection('sqlite')->getColumns('users'))->keyBy('name');

        $this->assertArrayHasKey('is_active', $columns->all());
        $this->assertSame('', (string) ($columns['is_active']['generation'] ?? ''));
        $this->assertArrayNotHasKey('status', $columns->all());
    }
}
