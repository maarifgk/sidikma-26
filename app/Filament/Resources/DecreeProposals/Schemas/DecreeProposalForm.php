<?php

namespace App\Filament\Resources\DecreeProposals\Schemas;

use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\School;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class DecreeProposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('NAMA')
                    ->placeholder('Masukan Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('employee_code')
                    ->label('EWANUGK')
                    ->placeholder('Masukan Nomor EWANUGK')
                    ->required()
                    ->maxLength(50)
                    ->regex('/^[A-Z0-9._\/-]+$/i')
                    ->unique('employees', 'employee_code')
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null),
                TextInput::make('phone')
                    ->label('NOMOR TELEPON')
                    ->placeholder('Masukan Nomor Telepon')
                    ->tel()
                    ->required()
                    ->maxLength(30),
                TextInput::make('email')
                    ->label('EMAIL')
                    ->placeholder('Masukan Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique('employees', 'email')
                    ->rules([Rule::unique('users', 'email')])
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => filled($state)
                        ? strtolower(trim($state))
                        : null)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                        ? strtolower(trim($state))
                        : null),
                Select::make('employment_status')
                    ->label('STATUS KEPEGAWAIAN')
                    ->placeholder('-- Pilih --')
                    ->options(Employee::employmentStatusOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if (! Employee::isPnsStatus($state)) {
                            $set('nip', null);
                            $set('rank', null);
                            $set('grade', null);
                        }
                    })
                    ->native(false),
                Select::make('school_id')
                    ->label('SEKOLAH/MADRASAH')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => School::activeOptionsFor())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),
                TextInput::make('nip')
                    ->label('NIP')
                    ->placeholder('Masukkan 18 digit NIP')
                    ->length(18)
                    ->regex('/^[0-9]{18}$/')
                    ->unique('employees', 'nip')
                    ->required(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status')))
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                TextInput::make('rank')
                    ->label('PANGKAT')
                    ->placeholder('Masukkan pangkat')
                    ->maxLength(100)
                    ->required(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status')))
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                TextInput::make('grade')
                    ->label('GOLONGAN')
                    ->placeholder('Masukkan golongan')
                    ->maxLength(50)
                    ->required(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status')))
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                DatePicker::make('birth_date')
                    ->label('TANGGAL LAHIR')
                    ->required()
                    ->maxDate(now()),
                TextInput::make('birth_place')
                    ->label('TEMPAT LAHIR')
                    ->placeholder('Masukan Tempat Lahir')
                    ->required()
                    ->maxLength(100),
                Select::make('position_id')
                    ->label('KETUGASAN')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => EmployeePosition::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),
                DatePicker::make('assignment_start_date')
                    ->label('TMT PERTAMA')
                    ->required(),
                TextInput::make('last_education')
                    ->label('PENDIDIKAN TERAKHIR, TAHUN LULUS')
                    ->placeholder('Contoh S1,2025')
                    ->required()
                    ->maxLength(100),
                TextInput::make('nuptk')
                    ->label('NUPTK (JIKA MEMILIKI)')
                    ->placeholder('Masukan Nomor NUPTK')
                    ->nullable()
                    ->maxLength(30)
                    ->regex('/^[A-Z0-9._\/-]+$/i')
                    ->unique('employees', 'nuptk')
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null),
                TextInput::make('application_password')
                    ->label('PASSWORD APLIKASI')
                    ->placeholder('Masukan Password Aplikasi')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->minLength(8)
                    ->maxLength(255),
                TextInput::make('program_study')
                    ->label('PROGRAM STUDI')
                    ->placeholder('Masukan Program Studi')
                    ->required()
                    ->maxLength(150),
                TextInput::make('application_password_confirmation')
                    ->label('KONFIRMASI PASSWORD')
                    ->placeholder('Masukan Ulang Password')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->same('application_password')
                    ->dehydrated(false),
                FileUpload::make('photo_path')
                    ->label('FOTO RESMI')
                    ->disk('decree-proposals')
                    ->visibility('private')
                    ->directory('photos')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(1024000)
                    ->required(),
                FileUpload::make('diploma_path')
                    ->label('IJAZAH TERAKHIR (PDF)')
                    ->disk('decree-proposals')
                    ->visibility('private')
                    ->directory('diplomas')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->required(),
                FileUpload::make('application_letter_path')
                    ->label('SURAT PERMOHONAN (PDF)')
                    ->disk('decree-proposals')
                    ->visibility('private')
                    ->directory('application-letters')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->required(),
                FileUpload::make('service_statement_path')
                    ->label('SURAT PERNYATAAN SIAP BERHIDMAD (PDF)')
                    ->disk('decree-proposals')
                    ->visibility('private')
                    ->directory('service-statements')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->required(),
                FileUpload::make('teaching_certificate_path')
                    ->label('AKTA MENGAJAR (BAGI YANG MEMILIKI)')
                    ->nullable()
                    ->disk('decree-proposals')
                    ->visibility('private')
                    ->directory('teaching-certificates')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(1024000),
                FileUpload::make('educator_certificate_path')
                    ->label('SERTIFIKAT PENDIDIK (BAGI YANG MEMILIKI)')
                    ->nullable()
                    ->disk('decree-proposals')
                    ->visibility('private')
                    ->directory('educator-certificates')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(1024000),
                FileUpload::make('task_assignment_certificate_path')
                    ->label('SERTIFIKAT PEMBAGIAN TUGAS DARI KEPALA SEKOLAH/MADRASAH')
                    ->disk('decree-proposals')
                    ->visibility('private')
                    ->directory('task-assignment-certificates')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(1024000)
                    ->required(),
            ])
            ->columns(2);
    }
}
