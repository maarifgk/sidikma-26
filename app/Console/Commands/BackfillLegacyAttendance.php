<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\School;
use App\Support\SidikmaMasterData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BackfillLegacyAttendance extends Command
{
    protected $signature = 'sidikma:backfill-attendance {--dry-run : Show the migration plan without writing data} {--force : Write without confirmation}';

    protected $description = 'Backfill attendance_records and attendance_events from the legacy attendances table';

    public function handle(): int
    {
        $connection = DB::connection('sidikma_induk');
        $schema = Schema::connection('sidikma_induk');

        foreach (['attendances', 'kelas', 'attendance_records'] as $table) {
            if (! $schema->hasTable($table)) {
                $this->error("Tabel {$table} tidak ditemukan pada koneksi sidikma_induk.");

                return self::FAILURE;
            }
        }

        $source = $connection->table('attendances')->orderBy('id')->get();
        if ($source->isEmpty()) {
            $this->warn('Tabel attendances tidak berisi data.');

            return self::SUCCESS;
        }

        $classes = $connection->table('kelas')->pluck('nama_kelas', 'id');
        $schools = School::query()->get()->keyBy(fn (School $school): string => SidikmaMasterData::normalized($school->name));
        $employees = Employee::query()->withTrashed()->whereNotNull('user_id')->get()->keyBy('user_id');
        $userIds = $connection->table('users')->pluck('id')->flip();
        $groups = $source->groupBy(fn (object $row): string => $row->user_id.'|'.$row->attendance_date);

        $records = [];
        $events = [];
        $skippedUser = 0;
        $skippedSchool = 0;
        $rejectedRows = 0;

        foreach ($groups as $group) {
            $last = $group->last();
            $employee = $employees->get($last->user_id);
            if (! $employee || ! $userIds->has($last->user_id)) {
                $skippedUser += $group->count();
                continue;
            }

            $className = $classes->get($last->kelas_id);
            $school = $className ? $schools->get(SidikmaMasterData::normalized($className)) : null;
            if (! $school) {
                $skippedSchool += $group->count();
                continue;
            }

            $accepted = $group->reject(fn (object $row): bool => $this->isRejected($row));
            $checkIn = $accepted->first(fn (object $row): bool => $this->eventType($row) === 'check_in' && filled($this->value($row, ['check_in_at', 'checked_at'])))
                ?? $accepted->first(fn (object $row): bool => filled($this->value($row, ['check_in_at'])));
            $checkOut = $accepted->reverse()->first(fn (object $row): bool => $this->eventType($row) === 'check_out' && filled($this->value($row, ['check_out_at', 'checked_at'])))
                ?? $accepted->reverse()->first(fn (object $row): bool => filled($this->value($row, ['check_out_at'])));

            if ($accepted->isNotEmpty()) {
                $isLate = $accepted->contains(fn (object $row): bool => Str::contains(Str::lower((string) $row->status), ['late', 'terlambat']));
                $records[] = [
                    'user_id' => $last->user_id,
                    'employee_id' => $employee->id,
                    'school_id' => $school->id,
                    'attendance_date' => $last->attendance_date,
                    'status' => $isLate ? 'late' : 'present',
                    'check_in_at' => $this->value($checkIn, ['check_in_at', 'checked_at']),
                    'check_out_at' => $this->value($checkOut, ['check_out_at', 'checked_at']),
                    'check_in_latitude' => $this->value($checkIn, ['check_in_latitude', 'latitude']),
                    'check_in_longitude' => $this->value($checkIn, ['check_in_longitude', 'longitude']),
                    'check_in_accuracy' => $this->value($checkIn, ['check_in_gps_accuracy', 'gps_accuracy']),
                    'check_out_latitude' => $this->value($checkOut, ['check_out_latitude', 'latitude']),
                    'check_out_longitude' => $this->value($checkOut, ['check_out_longitude', 'longitude']),
                    'check_out_accuracy' => $this->value($checkOut, ['check_out_gps_accuracy', 'gps_accuracy']),
                    'check_in_fake_gps' => (bool) $this->value($checkIn, ['check_in_is_mock_location', 'is_mock_location']),
                    'check_out_fake_gps' => (bool) $this->value($checkOut, ['check_out_is_mock_location', 'is_mock_location']),
                    'check_in_fake_gps_source' => $this->value($checkIn, ['check_in_mock_detection_source', 'mock_detection_source']),
                    'check_out_fake_gps_source' => $this->value($checkOut, ['check_out_mock_detection_source', 'mock_detection_source']),
                    'check_in_selfie_path' => $this->value($checkIn, ['check_in_selfie_path', 'selfie_path']),
                    'check_out_selfie_path' => $this->value($checkOut, ['check_out_selfie_path', 'selfie_path']),
                    'notes' => null,
                    'created_at' => $group->pluck('created_at')->filter()->min() ?? now(),
                    'updated_at' => $group->pluck('updated_at')->filter()->max() ?? now(),
                ];
            }

            foreach ($group as $row) {
                $rejected = $this->isRejected($row);
                $rejectedRows += (int) $rejected;
                $type = $this->eventType($row);
                $events[] = [
                    'legacy_id' => $row->id,
                    'user_id' => $row->user_id,
                    'employee_id' => $employee->id,
                    'school_id' => $school->id,
                    'attendance_date' => $row->attendance_date,
                    'event_type' => $type,
                    'event_status' => $rejected ? 'rejected' : 'accepted',
                    'checked_at' => $this->value($row, [$type.'_at', 'checked_at']),
                    'rejection_code' => $this->value($row, [$type.'_rejection_code', 'rejection_code']),
                    'rejection_reason' => $this->value($row, [$type.'_rejection_reason', 'rejection_reason']),
                    'is_mock_location' => $this->value($row, [$type.'_is_mock_location', 'is_mock_location']),
                    'mock_detection_source' => $this->value($row, [$type.'_mock_detection_source', 'mock_detection_source']),
                    'latitude' => $this->value($row, [$type.'_latitude', 'latitude']),
                    'longitude' => $this->value($row, [$type.'_longitude', 'longitude']),
                    'gps_accuracy' => $this->value($row, [$type.'_gps_accuracy', 'gps_accuracy']),
                    'is_inside_geofence' => $this->value($row, [$type.'_is_inside_geofence', 'is_inside_geofence']),
                    'selfie_path' => $this->value($row, [$type.'_selfie_path', 'selfie_path']),
                    'device_info' => $this->value($row, [$type.'_device_info', 'device_info']),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ];
            }
        }

        $this->table(['Sumber', 'Jumlah'], [
            ['Baris attendances', $source->count()],
            ['Rekap harian siap dipindahkan', count($records)],
            ['Kejadian siap dipindahkan', count($events)],
            ['Percobaan ditolak', $rejectedRows],
            ['Baris dilewati: user/pegawai tidak cocok', $skippedUser],
            ['Baris dilewati: sekolah tidak cocok', $skippedSchool],
        ]);

        if ($this->option('dry-run')) {
            $this->info('Dry run selesai. Database tidak diubah.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Lanjutkan backfill data presensi?')) {
            return self::SUCCESS;
        }

        $beforeRecords = $connection->table('attendance_records')->count();
        $beforeEvents = $schema->hasTable('attendance_events') ? $connection->table('attendance_events')->count() : 0;

        $connection->transaction(function () use ($connection, $schema, $records, $events): void {
            foreach (array_chunk($records, 500) as $chunk) {
                $connection->table('attendance_records')->insertOrIgnore($chunk);
            }

            if (! $schema->hasTable('attendance_events')) {
                return;
            }

            $recordIds = $connection->table('attendance_records')
                ->get(['id', 'employee_id', 'attendance_date'])
                ->keyBy(fn (object $row): string => $row->employee_id.'|'.$row->attendance_date);
            $existing = $connection->table('attendance_events')
                ->get(['user_id', 'attendance_date', 'event_type', 'checked_at', 'event_status'])
                ->mapWithKeys(fn (object $row): array => [$this->eventFingerprint((array) $row) => true]);

            $eventRows = collect($events)->reject(function (array $event) use ($existing): bool {
                $fingerprint = $this->eventFingerprint($event);
                if ($existing->has($fingerprint)) {
                    return true;
                }
                $existing->put($fingerprint, true);
                return false;
            })->map(function (array $event) use ($recordIds): array {
                unset($event['legacy_id']);
                $record = $recordIds->get($event['employee_id'].'|'.$event['attendance_date']);
                $event['attendance_id'] = $record?->id;
                return $event;
            })->values()->all();

            foreach (array_chunk($eventRows, 500) as $chunk) {
                $connection->table('attendance_events')->insert($chunk);
            }
        });

        $afterRecords = $connection->table('attendance_records')->count();
        $afterEvents = $schema->hasTable('attendance_events') ? $connection->table('attendance_events')->count() : 0;
        $this->info('Backfill selesai. Rekap baru: '.($afterRecords - $beforeRecords).'; kejadian baru: '.($afterEvents - $beforeEvents).'.');

        return self::SUCCESS;
    }

    private function isRejected(object $row): bool
    {
        return Str::contains(Str::lower((string) ($row->status ?? '')), ['rejected', 'ditolak', 'reject']);
    }

    private function eventType(object $row): string
    {
        $type = Str::lower((string) ($row->check_type ?? ''));
        return Str::contains($type, ['pulang', 'keluar', 'check_out', 'checkout', 'out']) ? 'check_out' : 'check_in';
    }

    private function value(?object $row, array $keys): mixed
    {
        if (! $row) {
            return null;
        }
        foreach ($keys as $key) {
            if (property_exists($row, $key) && $row->{$key} !== null && $row->{$key} !== '') {
                return $row->{$key};
            }
        }
        return null;
    }

    private function eventFingerprint(array $event): string
    {
        return implode('|', [
            $event['user_id'] ?? '',
            (string) ($event['attendance_date'] ?? ''),
            $event['event_type'] ?? '',
            (string) ($event['checked_at'] ?? ''),
            $event['event_status'] ?? '',
        ]);
    }
}
