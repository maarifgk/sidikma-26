<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        // SQLite :memory: creates a separate database for every PDO. Domain
        // models use sidikma_induk, so use the repository's test file to let
        // both connections see the same migrated schema.
        if ($connection === 'sqlite' && $database === ':memory:') {
            $database = 'database/testing.sqlite';
            $app['config']->set('database.connections.sqlite.database', $database);
        }

        $approvedExternalDatabase = rtrim((string) getenv('LOCALAPPDATA'), '\\/')
            . DIRECTORY_SEPARATOR . 'SIDIKMA26'
            . DIRECTORY_SEPARATOR . 'testing'
            . DIRECTORY_SEPARATOR . 'sidikma_gps20_test.sqlite';

        $databaseIsApprovedExternalSqlite = $connection === 'sqlite'
            && ! is_link($approvedExternalDatabase)
            && realpath($database) !== false
            && realpath($database) === realpath($approvedExternalDatabase);

        $databaseIsTestingSqlite = $connection === 'sqlite'
            && (in_array($database, [':memory:', 'database/testing.sqlite'], true) || $databaseIsApprovedExternalSqlite);

        if (! $databaseIsTestingSqlite) {
            throw new \RuntimeException(
                'Pengujian dibatalkan: database test wajib menggunakan SQLite testing. '
                . "Koneksi aktif: {$connection}; database: {$database}. "
                . 'Jalankan php artisan config:clear sebelum menjalankan test.',
            );
        }

        // Domain models use the named sidikma_induk connection in production.
        // During tests it must point at the same in-memory SQLite database so
        // RefreshDatabase migrates and resets one schema for every test.
        $app['config']->set(
            'database.connections.sidikma_induk',
            $app['config']->get('database.connections.sqlite'),
        );

        $journalMode = $databaseIsApprovedExternalSqlite ? 'DELETE' : 'WAL';
        $app['config']->set('database.connections.sqlite.journal_mode', $journalMode);
        $app['config']->set('database.connections.sidikma_induk.journal_mode', $journalMode);

        $sqlite = $app['db']->connection('sqlite');
        $app['db']->purge('sidikma_induk');
        $sidikma = $app['db']->connection('sidikma_induk');
        $sidikma->setPdo($sqlite->getPdo());
        $sidikma->setReadPdo($sqlite->getReadPdo());

        return $app;
    }
}
