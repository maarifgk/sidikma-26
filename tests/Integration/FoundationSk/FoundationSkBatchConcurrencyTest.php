<?php
namespace Tests\Integration\FoundationSk;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
class FoundationSkBatchConcurrencyTest extends TestCase { use RefreshDatabase; public function test_parallel_batches_require_mysql_or_postgresql():void {if(!in_array(config('database.default'),['mysql','pgsql'],true)){$this->markTestSkipped('Integration concurrency Foundation SK memerlukan MySQL/PostgreSQL untuk memvalidasi lockForUpdate.');} $this->markTestSkipped('Jalankan suite ini pada database worker terpisah untuk parallel process.');} }
