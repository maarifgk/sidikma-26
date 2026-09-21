<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaUsers extends Command
{
    /**
     * Nama command.
     */
    protected $signature = 'sidikma:migrate-users
                            {--dry-run : Analisis data tanpa melakukan perubahan}
                            {--force : Jalankan migrasi tanpa konfirmasi}';

    /**
     * Deskripsi command.
     */
    protected $description = 'Migrasi users SIDIKMA lama dari MariaDB ke PostgreSQL';

    /**
     * Mapping role SIDIKMA lama ke role PostgreSQL baru.
     */
    private array $roleMapping = [
        0 => 9,    // Legacy Guru/Pegawai -> guru-pegawai
        1 => 7,    // Super Admin -> admin-induk
        2 => 9,    // Guru/Pegawai -> guru-pegawai
        3 => 8,    // Admin Sekolah -> admin-sekolah-madrasah
        4 => 10,   // Pengurus -> pengurus
    ];

    /**
     * Mapping user SIDIKMA lama yang sudah mempunyai
     * akun pengganti di PostgreSQL.
     */
    private array $userIdMapping = [
        1833 => 11,
    ];

    /**
     * User SIDIKMA yang sengaja tidak dimigrasikan.
     *
     * ID 518 merupakan record kosong dan tidak mempunyai
     * relasi pada tabel yang mereferensikan users.id.
     */
    private array $skipUserIds = [
        518,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('==============================================');
        $this->info($dryRun ? ' DRY RUN MIGRASI USERS SIDIKMA' : ' MIGRASI USERS SIDIKMA');
        if ($dryRun) {
            $this->info(' Tidak ada data yang akan diubah');
        }
        $this->info('==============================================');
        $this->newLine();

        /**
         * Ambil users SIDIKMA lama.
         */
        $oldUsers = DB::connection('mysql_sidikma')
            ->table('users')
            ->select([
                'id',
                'nama_lengkap',
                'email',
                'password',
                'status',
                'role',
                'no_tlp',
                'image',
                'email_verified_at',
                'remember_token',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->get();

        /**
         * Ambil users PostgreSQL.
         */
        $newUsers = DB::connection('pgsql')
            ->table('users')
            ->select([
                'id',
                'email',
            ])
            ->get();

        /**
         * Index user PostgreSQL berdasarkan ID.
         */
        $existingIds = [];

        /**
         * Index user PostgreSQL berdasarkan email.
         */
        $existingEmails = [];

        foreach ($newUsers as $user) {
            $email = strtolower(
                trim((string) $user->email)
            );

            $existingIds[(int) $user->id] = $email;

            if ($email !== '') {
                $existingEmails[$email] = (int) $user->id;
            }
        }

        /**
         * Statistik.
         */
        $stats = [
            'total_old' => $oldUsers->count(),
            'total_new' => $newUsers->count(),

            'eligible' => 0,

            'already_exists' => 0,
            'mapped_existing' => 0,
            'mapping_error' => 0,

            /**
             * User yang sengaja dilewati.
             */
            'skipped' => 0,

            'id_conflict' => 0,
            'email_conflict' => 0,

            'missing_name' => 0,
            'missing_email' => 0,
            'missing_password' => 0,

            'status_on' => 0,
            'status_off' => 0,
            'status_null' => 0,

            'role_0' => 0,
            'role_1' => 0,
            'role_2' => 0,
            'role_3' => 0,
            'role_4' => 0,

            'unknown_role' => 0,
            'unsupported_role' => 0,
        ];

        /**
         * Maksimal 20 contoh masalah.
         */
        $problems = [];

        /** @var array<int, array{user: array<string, mixed>, role_id: int}> $usersToMigrate */
        $usersToMigrate = [];

        foreach ($oldUsers as $user) {
            $id = (int) $user->id;

            /**
             * ==========================================
             * USER YANG SENGAJA DILEWATI
             * ==========================================
             *
             * Contoh:
             * ID 518 merupakan record kosong
             * dan tidak memiliki relasi.
             */
            if (
                in_array(
                    $id,
                    $this->skipUserIds,
                    true
                )
            ) {
                $stats['skipped']++;

                continue;
            }

            $name = trim(
                (string) $user->nama_lengkap
            );

            $email = strtolower(
                trim((string) $user->email)
            );

            $password = trim(
                (string) $user->password
            );

            $role = (int) $user->role;

            /**
             * ==========================================
             * HITUNG STATUS
             * ==========================================
             */
            if ($user->status === 'ON') {
                $stats['status_on']++;
            } elseif ($user->status === 'OFF') {
                $stats['status_off']++;
            } else {
                $stats['status_null']++;
            }

            /**
             * ==========================================
             * HITUNG ROLE
             * ==========================================
             */
            if (
                in_array(
                    $role,
                    [0, 1, 2, 3, 4],
                    true
                )
            ) {
                $stats['role_'.$role]++;
            }

            /**
             * ==========================================
             * VALIDASI NAMA
             * ==========================================
             */
            if ($name === '') {
                $stats['missing_name']++;

                $this->addProblem(
                    $problems,
                    $id,
                    'Nama kosong'
                );

                continue;
            }

            /**
             * ==========================================
             * VALIDASI EMAIL
             * ==========================================
             */
            if ($email === '') {
                $stats['missing_email']++;

                $this->addProblem(
                    $problems,
                    $id,
                    'Email kosong'
                );

                continue;
            }

            /**
             * ==========================================
             * VALIDASI PASSWORD
             * ==========================================
             */
            if ($password === '') {
                $stats['missing_password']++;

                $this->addProblem(
                    $problems,
                    $id,
                    'Password kosong'
                );

                continue;
            }

            /**
             * ==========================================
             * VALIDASI ROLE
             * ==========================================
             */
            if (
                ! array_key_exists(
                    $role,
                    $this->roleMapping
                )
            ) {
                $stats['unknown_role']++;

                $this->addProblem(
                    $problems,
                    $id,
                    'Role tidak dikenal: '.$role
                );

                continue;
            }

            /**
             * Role Pengurus belum tersedia.
             */
            if ($this->roleMapping[$role] === null) {
                $stats['unsupported_role']++;

                $this->addProblem(
                    $problems,
                    $id,
                    'Role Pengurus belum tersedia di PostgreSQL'
                );

                continue;
            }

            /**
             * ==========================================
             * USER ID MAPPING KHUSUS
             * ==========================================
             */
            if (
                array_key_exists(
                    $id,
                    $this->userIdMapping
                )
            ) {
                $targetId = $this->userIdMapping[$id];

                /**
                 * Pastikan target ID ada.
                 */
                if (
                    ! array_key_exists(
                        $targetId,
                        $existingIds
                    )
                ) {
                    $stats['mapping_error']++;

                    $this->addProblem(
                        $problems,
                        $id,
                        'Target mapping PostgreSQL ID '
                        .$targetId
                        .' tidak ditemukan'
                    );

                    continue;
                }

                /**
                 * Pastikan email sama.
                 */
                if (
                    $existingIds[$targetId] !== $email
                ) {
                    $stats['mapping_error']++;

                    $this->addProblem(
                        $problems,
                        $id,
                        'Email mapping tidak cocok dengan PostgreSQL ID '
                        .$targetId
                    );

                    continue;
                }

                $stats['mapped_existing']++;

                continue;
            }

            /**
             * ==========================================
             * CEK ID POSTGRESQL
             * ==========================================
             */
            $idExists = array_key_exists(
                $id,
                $existingIds
            );

            /**
             * ==========================================
             * CEK EMAIL POSTGRESQL
             * ==========================================
             */
            $emailExists = array_key_exists(
                $email,
                $existingEmails
            );

            /**
             * ID dan email sama persis.
             */
            if (
                $idExists &&
                $existingIds[$id] === $email
            ) {
                $stats['already_exists']++;

                continue;
            }

            /**
             * ID sama tetapi email berbeda.
             */
            if ($idExists) {
                $stats['id_conflict']++;

                $this->addProblem(
                    $problems,
                    $id,
                    'ID sudah digunakan PostgreSQL'
                );

                continue;
            }

            /**
             * Email sama tetapi ID berbeda.
             */
            if ($emailExists) {
                $stats['email_conflict']++;

                $this->addProblem(
                    $problems,
                    $id,
                    'Email sudah digunakan oleh user PostgreSQL ID '
                    .$existingEmails[$email]
                );

                continue;
            }

            /**
             * ==========================================
             * SIAP DIMIGRASIKAN
             * ==========================================
             */
            $stats['eligible']++;

            $usersToMigrate[] = [
                'user' => [
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'email_verified_at' => $this->nullableDate($user->email_verified_at),
                    'phone_number' => $this->nullableTrim($user->no_tlp),
                    'avatar_path' => $this->nullableTrim($user->image),
                    'password' => $password,
                    'remember_token' => $user->remember_token,
                    'is_active' => $user->status === 'ON',
                    'created_at' => $this->nullableDate($user->created_at),
                    'updated_at' => $this->nullableDate($user->updated_at),
                    'created_by' => null,
                    'updated_by' => null,
                ],
                'role_id' => $this->roleMapping[$role],
            ];
        }

        /**
         * ==============================================
         * HASIL ANALISIS
         * ==============================================
         */
        $this->newLine();

        $this->info('HASIL ANALISIS');

        $this->line(
            '----------------------------------------------'
        );

        $this->table(
            [
                'Pemeriksaan',
                'Jumlah',
            ],
            [
                [
                    'Users SIDIKMA lama',
                    $stats['total_old'],
                ],
                [
                    'Users PostgreSQL sekarang',
                    $stats['total_new'],
                ],
                [
                    'Siap dimigrasikan',
                    $stats['eligible'],
                ],
                [
                    'Sudah ada / sama',
                    $stats['already_exists'],
                ],
                [
                    'Sudah dipetakan ke user baru',
                    $stats['mapped_existing'],
                ],
                [
                    'Sengaja dilewati',
                    $stats['skipped'],
                ],
                [
                    'Error mapping user',
                    $stats['mapping_error'],
                ],
                [
                    'Bentrok ID',
                    $stats['id_conflict'],
                ],
                [
                    'Bentrok email',
                    $stats['email_conflict'],
                ],
                [
                    'Nama kosong',
                    $stats['missing_name'],
                ],
                [
                    'Email kosong',
                    $stats['missing_email'],
                ],
                [
                    'Password kosong',
                    $stats['missing_password'],
                ],
                [
                    'Role belum didukung',
                    $stats['unsupported_role'],
                ],
                [
                    'Role tidak dikenal',
                    $stats['unknown_role'],
                ],
            ]
        );

        /**
         * ==============================================
         * STATUS SIDIKMA
         * ==============================================
         */
        $this->newLine();

        $this->info('STATUS SIDIKMA');

        $this->table(
            [
                'Status',
                'Jumlah',
            ],
            [
                [
                    'ON',
                    $stats['status_on'],
                ],
                [
                    'OFF',
                    $stats['status_off'],
                ],
                [
                    'NULL / lainnya',
                    $stats['status_null'],
                ],
            ]
        );

        /**
         * ==============================================
         * ROLE SIDIKMA
         * ==============================================
         */
        $this->newLine();

        $this->info('ROLE SIDIKMA');

        $this->table(
            [
                'Role',
                'Mapping PostgreSQL',
                'Jumlah',
            ],
            [
                [
                    '0 - Guru/Pegawai (Legacy)',
                    '9 - guru-pegawai',
                    $stats['role_0'],
                ],
                [
                    '1 - Super Admin',
                    '7 - admin-induk',
                    $stats['role_1'],
                ],
                [
                    '2 - Guru/Pegawai',
                    '9 - guru-pegawai',
                    $stats['role_2'],
                ],
                [
                    '3 - Admin Sekolah',
                    '8 - admin-sekolah-madrasah',
                    $stats['role_3'],
                ],
                [
                    '4 - Pengurus',
                    '10 - pengurus',
                    $stats['role_4'],
                ],
            ]
        );

        /**
         * ==============================================
         * USER ID MAPPING
         * ==============================================
         */
        $this->newLine();

        $this->info('USER ID MAPPING');

        $mappingRows = [];

        foreach (
            $this->userIdMapping as $oldId => $newId
        ) {
            $mappingRows[] = [
                $oldId,
                $newId,
            ];
        }

        $this->table(
            [
                'ID SIDIKMA',
                'ID PostgreSQL',
            ],
            $mappingRows
        );

        /**
         * ==============================================
         * USER YANG DILEWATI
         * ==============================================
         */
        $this->newLine();

        $this->info('USER YANG SENGAJA DILEWATI');

        $skipRows = [];

        foreach ($this->skipUserIds as $skipId) {
            $skipRows[] = [
                $skipId,
                'Record kosong / tidak memiliki relasi',
            ];
        }

        $this->table(
            [
                'ID SIDIKMA',
                'Alasan',
            ],
            $skipRows
        );

        /**
         * ==============================================
         * CONTOH MASALAH
         * ==============================================
         */
        if (count($problems) > 0) {
            $this->newLine();

            $this->warn(
                'CONTOH DATA YANG PERLU DIPERIKSA'
            );

            $this->table(
                [
                    'ID SIDIKMA',
                    'Masalah',
                ],
                array_slice(
                    $problems,
                    0,
                    20
                )
            );
        }

        $this->newLine();

        $this->info(
            '=============================================='
        );

        if ($dryRun) {
            $this->info(' DRY RUN SELESAI');
            $this->info(' Tidak ada perubahan pada PostgreSQL.');
        } else {
            if (! $this->option('force') && ! $this->confirm(
                "Migrasikan {$stats['eligible']} user beserta role ke PostgreSQL?",
            )) {
                $this->warn(' Migrasi dibatalkan. Tidak ada perubahan pada PostgreSQL.');
                $this->info('==============================================');

                return self::SUCCESS;
            }

            try {
                $this->migrateUsers($usersToMigrate);
            } catch (Throwable $exception) {
                report($exception);
                $this->error(' Migrasi gagal. Seluruh perubahan telah di-rollback.');
                $this->error($exception->getMessage());
                $this->info('==============================================');

                return self::FAILURE;
            }

            $this->info(" MIGRASI SELESAI: {$stats['eligible']} user berhasil dibuat beserta role.");
        }

        $this->info(
            '=============================================='
        );

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{user: array<string, mixed>, role_id: int}>  $users
     */
    private function migrateUsers(array $users): void
    {
        DB::connection('pgsql')->transaction(function () use ($users): void {
            foreach (array_chunk($users, 100) as $chunk) {
                DB::connection('pgsql')->table('users')->insert(
                    array_column($chunk, 'user'),
                );

                DB::connection('pgsql')->table('model_has_roles')->insert(
                    array_map(
                        fn (array $item): array => [
                            'role_id' => $item['role_id'],
                            'model_type' => User::class,
                            'model_id' => $item['user']['id'],
                        ],
                        $chunk,
                    ),
                );
            }

            DB::connection('pgsql')->statement(<<<'SQL'
                SELECT setval(
                    pg_get_serial_sequence('users', 'id'),
                    COALESCE((SELECT MAX(id) FROM users), 1),
                    true
                )
                SQL);
        });
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = $this->nullableTrim($value);

        if ($value === null || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return $value;
    }

    /**
     * Tambahkan maksimal 20 contoh masalah.
     */
    private function addProblem(
        array &$problems,
        int $id,
        string $reason
    ): void {
        if (count($problems) < 20) {
            $problems[] = [
                $id,
                $reason,
            ];
        }
    }
}
