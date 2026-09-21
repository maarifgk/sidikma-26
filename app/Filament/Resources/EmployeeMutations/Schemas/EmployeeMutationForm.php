<?php

namespace App\Filament\Resources\EmployeeMutations\Schemas;

use App\Models\Employee;
use App\Models\EmployeeMutation;
use App\Models\School;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EmployeeMutationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('employee_id'),
                TextInput::make('employee_name')
                    ->label('NAMA')
                    ->placeholder('Masukan Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('employee_code')
                    ->label(fn (Get $get): string => $get('mutation_type') === EmployeeMutation::TYPE_INCOMING
                        ? 'EWANUGK (OPSIONAL)'
                        : 'EWANUGK')
                    ->placeholder('Masukan Nomor EWANUGK')
                    ->required(fn (Get $get): bool => $get('mutation_type') !== EmployeeMutation::TYPE_INCOMING)
                    ->maxLength(50)
                    ->regex('/^[A-Z0-9._\/-]+$/i')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $employee = Employee::query()
                            ->with('school')
                            ->when(
                                auth()->user() instanceof User,
                                fn ($query) => $query->whereIn(
                                    'school_id',
                                    auth()->user()->accessibleSchoolIds(),
                                ),
                            )
                            ->where('employee_code', strtoupper(trim((string) $state)))
                            ->first();

                        $set('employee_id', $employee?->getKey());
                        $set('employment_status', $employee?->employment_status);

                        if (! $employee) {
                            return;
                        }

                        $set('employee_name', $employee->name);
                        $set('phone', $employee->phone);
                        $set('birth_place', $employee->birth_place);
                        $set('birth_date', $employee->birth_date?->toDateString());
                        $set('origin_school_name', $employee->school?->name);
                    })
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null),
                TextInput::make('birth_place')
                    ->label('TEMPAT LAHIR')
                    ->placeholder('Masukan Tempat Lahir')
                    ->required()
                    ->maxLength(100),
                TextInput::make('phone')
                    ->label('NOMOR TELEPON')
                    ->placeholder('Masukan Nomor Telepon')
                    ->tel()
                    ->required()
                    ->maxLength(30)
                    ->regex('/^[0-9+\-\s().]+$/'),
                Select::make('mutation_type')
                    ->label('JENIS MUTASI')
                    ->placeholder('-- Pilih --')
                    ->options(EmployeeMutation::typeOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($state === EmployeeMutation::TYPE_OUTGOING) {
                            $set('destination_school_id', null);
                            $set('destination_school_name', null);
                        }

                        if ($state === EmployeeMutation::TYPE_INCOMING) {
                            $set('origin_school_id', null);
                        } else {
                            $set('origin_school_name', null);
                        }
                    })
                    ->native(false),
                DatePicker::make('birth_date')
                    ->label('TANGGAL LAHIR')
                    ->required()
                    ->maxDate(now()),
                Select::make('employment_status')
                    ->label('STATUS KEPEGAWAIAN')
                    ->placeholder('-- Pilih status kepegawaian --')
                    ->options(function (Get $get): array {
                        $options = Employee::employmentStatusOptions();
                        $status = self::accountEmploymentStatus($get('employee_code'));
                        if (filled($status)) {
                            $options[$status] = Employee::employmentStatusLabel($status);
                        }

                        return $options;
                    })
                    ->disabled(fn (Get $get): bool => filled(self::accountEmploymentStatus($get('employee_code'))))
                    ->dehydrated()
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->helperText('Terisi sesuai akun tenaga pendidik jika EWANUGK terdaftar.')
                    ->columnSpanFull(),
                TextInput::make('origin_school_name')
                    ->label('SEKOLAH/MADRASAH ASAL')
                    ->placeholder('Masukkan sekolah/madrasah asal dari luar Ma’arif')
                    ->required(fn (Get $get): bool => $get('mutation_type') === EmployeeMutation::TYPE_INCOMING)
                    ->visible(fn (Get $get): bool => $get('mutation_type') === EmployeeMutation::TYPE_INCOMING)
                    ->maxLength(255),
                Select::make('origin_school_id')
                    ->label('SEKOLAH/MADRASAH ASAL')
                    ->placeholder('-- Pilih sekolah asal --')
                    ->options(fn (): array => School::activeOptionsFor())
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('mutation_type') !== EmployeeMutation::TYPE_INCOMING)
                    ->visible(fn (Get $get): bool => $get('mutation_type') !== EmployeeMutation::TYPE_INCOMING)
                    ->native(false),
                Select::make('destination_school_id')
                    ->label('SEKOLAH/MADRASAH TUJUAN')
                    ->placeholder('-- Pilih sekolah tujuan --')
                    ->options(fn (): array => School::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('mutation_type') !== EmployeeMutation::TYPE_OUTGOING)
                    ->visible(fn (Get $get): bool => $get('mutation_type') !== EmployeeMutation::TYPE_OUTGOING)
                    ->different('origin_school_id')
                    ->native(false),
                FileUpload::make('request_letter_path')
                    ->label('UPLOAD SURAT PERMOHONAN MUTASI (PDF)')
                    ->disk(EmployeeMutation::DISK)
                    ->visibility('private')
                    ->directory('request-letters')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->downloadable()
                    ->openable()
                    ->previewable(false)
                    ->required()
                    ->helperText('Format PDF, maksimal 1000 MB.'),
            ])
            ->columns(2);
    }

    private static function accountEmploymentStatus(?string $employeeCode): ?string
    {
        if (blank($employeeCode) || ! auth()->user() instanceof User) {
            return null;
        }

        return Employee::query()
            ->whereIn('school_id', auth()->user()->accessibleSchoolIds())
            ->where('employee_code', strtoupper(trim($employeeCode)))
            ->value('employment_status');
    }
}
