<?php

namespace Tests\Unit;

use App\Filament\Resources\DecreeProposals\Pages\CreateDecreeProposal;
use App\Models\Activity;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
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
