<?php
namespace App\Services;
class ZipArchiveAvailability
{
 public function ensureAvailable(): void { if (! class_exists(\ZipArchive::class)) { throw new \RuntimeException('Export XLSX membutuhkan ekstensi PHP ZipArchive. Aktifkan extension=zip pada php.ini.'); } }
 public function available(): bool { return class_exists(\ZipArchive::class); }
}
