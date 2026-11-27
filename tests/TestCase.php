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

        $databaseIsTestingSqlite = $connection === 'sqlite'
            && in_array($database, [':memory:', 'database/testing.sqlite', 'database/testing-step9a.sqlite', 'database/testing-usulan-sk-v9.sqlite'], true);

        if (! $databaseIsTestingSqlite) {
            throw new \RuntimeException(
                'Pengujian dibatalkan: database test wajib menggunakan SQLite testing. '
                . "Koneksi aktif: {$connection}; database: {$database}. "
                . 'Jalankan php artisan config:clear sebelum menjalankan test.',
            );
        }

        // Domain models use the named sidikma_induk connection in production.
        // During tests it must point at the same SQLite file, but retain a
        // separate PDO. Filament opens a transaction on the default connection
        // while proposal creation opens its own transaction on sidikma_induk.
        $app['config']->set(
            'database.connections.sidikma_induk',
            $app['config']->get('database.connections.sqlite'),
        );

        $app['config']->set('database.connections.sqlite.journal_mode', 'WAL');
        $app['config']->set('database.connections.sidikma_induk.journal_mode', 'WAL');

        $app['db']->purge('sidikma_induk');
        $app['db']->connection('sidikma_induk');

        return $app;
    }
}
