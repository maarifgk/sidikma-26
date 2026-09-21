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

        if (($connection !== 'sqlite') || ($database !== ':memory:')) {
            throw new \RuntimeException(
                'Pengujian dibatalkan: database test wajib menggunakan SQLite :memory:. '
                . "Koneksi aktif: {$connection}; database: {$database}. "
                . 'Jalankan php artisan config:clear sebelum menjalankan test.',
            );
        }

        return $app;
    }
}
