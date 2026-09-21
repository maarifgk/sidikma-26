<?php

namespace App\Filament\Imports;

use App\Models\Employee;
use App\Models\Foundation;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    protected static bool $shouldPreventFormulaInjection = true;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('employee_code')
                ->label('Kode Pegawai')
                ->guess(['kode_pegawai', 'kode pegawai'])
                ->requiredMapping()
                ->example('PEG-0001')
                ->castStateUsing(fn (mixed $state): ?string => self::normalizeCode($state))
                ->rules(['required', 'string', 'max:50', 'regex:/^[A-Z0-9._\/-]+$/']),
            ImportColumn::make('name')
                ->label('Nama Lengkap')
                ->guess(['nama', 'nama_lengkap', 'nama lengkap'])
                ->requiredMappingForNewRecordsOnly()
                ->example('Ahmad Guru')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('employee_type')
                ->label('Jenis')
                ->guess(['jenis', 'jenis_pegawai', 'jenis pegawai'])
                ->requiredMappingForNewRecordsOnly()
                ->example(Employee::TYPE_GURU)
                ->castStateUsing(fn (mixed $state): ?string => filled($state) ? strtolower(trim((string) $state)) : null)
                ->rules(['required', Rule::in([
                    Employee::TYPE_GURU,
                    Employee::TYPE_PEGAWAI,
                ])]),
            ImportColumn::make('employment_status')
                ->label('Status Kepegawaian')
                ->guess(['status', 'status_kepegawaian', 'status kepegawaian'])
                ->example('GTY')
                ->castStateUsing(fn (mixed $state): ?string => self::normalizeEmploymentStatus($state))
                ->rules(['nullable', Rule::in(self::employmentStatuses())]),
            ImportColumn::make('school')
                ->label('NPSN Sekolah/Madrasah')
                ->exampleHeader('school_npsn')
                ->guess(['school_npsn', 'npsn', 'npsn_sekolah'])
                ->example('12345678')
                ->relationship(resolveUsing: 'npsn')
                ->rules(['nullable', 'digits:8']),
            ImportColumn::make('user')
                ->label('Email Akun User')
                ->exampleHeader('user_email')
                ->guess(['user_email', 'email_user', 'email akun user'])
                ->example('guru@example.test')
                ->castStateUsing(fn (mixed $state): ?string => self::normalizeEmail($state))
                ->relationship(resolveUsing: 'email')
                ->rules(fn (ImportColumn $column): array => [
                    'nullable',
                    'email',
                    function (string $attribute, mixed $state, mixed $fail) use ($column): void {
                        if (blank($state)) {
                            return;
                        }

                        $userId = User::query()->where('email', $state)->value('id');

                        if (blank($userId)) {
                            return;
                        }

                        $record = $column->getRecord();
                        $isAlreadyAssigned = Employee::query()
                            ->where('user_id', $userId)
                            ->when(
                                $record?->exists,
                                fn ($query) => $query->whereKeyNot($record->getKey()),
                            )
                            ->exists();

                        if ($isAlreadyAssigned) {
                            $fail('Akun user sudah terhubung dengan guru/pegawai lain.');
                        }
                    },
                ]),
            ImportColumn::make('nik')
                ->label('NIK')
                ->example('1234567890123456')
                ->rules(fn (ImportColumn $column): array => [
                    'nullable',
                    'digits:16',
                    Rule::unique('employees', 'nik')->ignore($column->getRecord()),
                ]),
            ImportColumn::make('nip')
                ->label('NIP')
                ->example('123456789012345678')
                ->rules(fn (ImportColumn $column): array => [
                    'nullable',
                    'digits:18',
                    Rule::unique('employees', 'nip')->ignore($column->getRecord()),
                ]),
            ImportColumn::make('nuptk')
                ->label('NUPTK')
                ->example('6543210987654321')
                ->rules(fn (ImportColumn $column): array => [
                    'nullable',
                    'digits:16',
                    Rule::unique('employees', 'nuptk')->ignore($column->getRecord()),
                ]),
            ImportColumn::make('gender')
                ->label('Jenis Kelamin')
                ->guess(['jenis_kelamin', 'jenis kelamin'])
                ->example(Employee::GENDER_MALE)
                ->castStateUsing(fn (mixed $state): ?string => filled($state) ? strtoupper(trim((string) $state)) : null)
                ->rules(['nullable', Rule::in([
                    Employee::GENDER_MALE,
                    Employee::GENDER_FEMALE,
                ])]),
            ImportColumn::make('birth_place')
                ->label('Tempat Lahir')
                ->guess(['tempat_lahir', 'tempat lahir'])
                ->example('Gunungkidul')
                ->rules(['nullable', 'string', 'max:100']),
            ImportColumn::make('birth_date')
                ->label('Tanggal Lahir')
                ->guess(['tanggal_lahir', 'tanggal lahir'])
                ->example('1990-05-10')
                ->rules(['nullable', 'date', 'before_or_equal:today']),
            ImportColumn::make('phone')
                ->label('Nomor Telepon')
                ->guess(['telepon', 'nomor_telepon', 'nomor telepon'])
                ->example('+62 812-3456-7890')
                ->rules(['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]+$/']),
            ImportColumn::make('email')
                ->label('Email Pegawai')
                ->guess(['email_pegawai', 'email pegawai'])
                ->example('ahmad@example.test')
                ->castStateUsing(fn (mixed $state): ?string => self::normalizeEmail($state))
                ->rules(fn (ImportColumn $column): array => [
                    'nullable',
                    'email',
                    'max:255',
                    Rule::unique('employees', 'email')->ignore($column->getRecord()),
                ]),
            ImportColumn::make('address')
                ->label('Alamat')
                ->example('Jalan Pendidikan Nomor 1')
                ->rules(['nullable', 'string', 'max:2000']),
            ImportColumn::make('is_active')
                ->label('Status Aktif')
                ->guess(['aktif', 'status_aktif', 'status aktif'])
                ->example('true')
                ->boolean()
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): Employee
    {
        $employee = Employee::query()->firstOrNew([
            'employee_code' => $this->data['employee_code'],
        ]);

        $employee->foundation_id ??= Foundation::application()->getKey();

        return $employee;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import guru/pegawai selesai. '
            .Number::format($import->successful_rows)
            .' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
                .Number::format($failedRowsCount)
                .' baris gagal dan dapat diunduh untuk diperbaiki.';
        }

        return $body;
    }

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    protected function beforeCreate(): void
    {
        abort_unless(auth()->user()?->isAdminInduk(), 403);

        Gate::authorize('create', Employee::class);
    }

    protected function beforeUpdate(): void
    {
        abort_unless(auth()->user()?->isAdminInduk(), 403);

        Gate::authorize('update', $this->record);
    }

    /**
     * @return array<int, string>
     */
    private static function employmentStatuses(): array
    {
        return array_keys(Employee::employmentStatusOptions());
    }

    private static function normalizeCode(mixed $code): ?string
    {
        return filled($code) ? strtoupper(trim((string) $code)) : null;
    }

    private static function normalizeEmail(mixed $email): ?string
    {
        return filled($email) ? strtolower(trim((string) $email)) : null;
    }

    private static function normalizeEmploymentStatus(mixed $status): ?string
    {
        if (blank($status)) {
            return null;
        }

        $status = trim((string) $status);

        foreach (Employee::employmentStatusOptions() as $value => $label) {
            if (strtolower($value) === strtolower($status)
                || strtolower($label) === strtolower($status)) {
                return $value;
            }
        }

        return $status;
    }
}
