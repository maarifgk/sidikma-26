<?php

namespace Tests\Unit;

use App\Filament\Resources\DecreeProposals\Pages\CreateDecreeProposal;
use App\Models\Activity;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DecreeProposalTransactionTest extends TestCase
{
    public function test_decree_proposal_creation_uses_one_connection_without_retries(): void
    {
        $file = (new ReflectionClass(CreateDecreeProposal::class))->getFileName();
        $source = file_get_contents($file);

        self::assertIsString($source);
        self::assertStringContainsString('DB::connection($connectionName)->transaction', $source);
        self::assertStringContainsString('Membership::on($connectionName)->create', $source);
        self::assertStringContainsString('EmployeeAssignment::on($connectionName)->create', $source);
        self::assertStringContainsString('DecreeProposal::on($connectionName)->create', $source);
        self::assertStringContainsString('Role::on($connectionName)', $source);
        self::assertStringContainsString('$account->setConnection($connectionName)', $source);
        self::assertStringNotContainsString('attempts:', $source);
    }

    public function test_legacy_required_user_fields_are_mapped_without_a_fake_nuptk(): void
    {
        $method = (new ReflectionClass(CreateDecreeProposal::class))->getMethod('buildUserData');
        $method->setAccessible(true);

        $data = [
            'name' => 'Test Guru',
            'email' => 'test@example.test',
            'phone' => '081234567890',
            'application_password' => 'password-test',
            'nuptk' => '1234567890123456',
            'employment_status' => 'GTY',
            'program_study' => 'Pendidikan',
            'birth_place' => 'Gunungkidul',
        ];

        $mapped = $method->invoke(null, $data, new User);

        self::assertSame('Test Guru', $mapped['nama_lengkap']);
        self::assertSame('1234567890123456', $mapped['nuptk']);
        self::assertSame('GTY', $mapped['ptt_lulus']);

        $data['nuptk'] = null;
        $mappedWithoutNuptk = $method->invoke(null, $data, new User);

        self::assertNull($mappedWithoutNuptk['nuptk']);
    }

    public function test_assignment_primary_check_uses_the_model_connection(): void
    {
        $file = (new ReflectionClass(EmployeeAssignment::class))->getFileName();
        $source = file_get_contents($file);

        self::assertIsString($source);
        self::assertStringContainsString('$this->newQuery()', $source);
        self::assertStringNotContainsString('self::query()', $source);
    }

    public function test_audit_activity_follows_the_subject_connection_when_no_override_is_configured(): void
    {
        $app = new Application(dirname(__DIR__, 2));
        Container::setInstance($app);
        $app->instance('config', new Repository([
            'activitylog' => ['database_connection' => null],
            'database' => ['default' => 'mysql'],
        ]));

        $employee = new Employee;
        $activity = new Activity;

        $employee->tapActivity($activity, 'created');

        self::assertSame('sidikma_induk', $activity->getConnectionName());
    }
}
