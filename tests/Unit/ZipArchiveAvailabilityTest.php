<?php
namespace Tests\Unit;
use App\Services\ZipArchiveAvailability;
use Tests\TestCase;
class ZipArchiveAvailabilityTest extends TestCase
{
 public function test_current_runtime_reports_ziparchive_availability(): void { $checker=new ZipArchiveAvailability(); $this->assertTrue($checker->available()); $checker->ensureAvailable(); }
 public function test_unavailable_message_is_explicit(): void { $this->expectExceptionMessage('ekstensi PHP ZipArchive'); throw new \RuntimeException('Export XLSX membutuhkan ekstensi PHP ZipArchive. Aktifkan extension=zip pada php.ini.'); }
}
