<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\School;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class EmployeeCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_code')
                    ->label('EWANUGK')
                    ->placeholder('Masukan Nomor eWanuGK')
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
                TextInput::make('name')
                    ->label('NAMA LENGKAP')
                    ->placeholder('Masukan Nama Lengkap')
                    ->required()
                    ->maxLength(255),
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
                TextInput::make('nuptk')
                    ->label('NUPTK/NPK')
                    ->placeholder('Masukan NUPTK/NPK')
                    ->maxLength(30)
                    ->regex('/^[A-Z0-9._\/-]+$/i')
                    ->unique('employees', 'nuptk')
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                        ? strtoupper(trim($state))
                        : null),
                TextInput::make('last_education')
                    ->label('PENDIDIKAN TERAKHIR DAN TAHUN LULUS')
                    ->placeholder('Contoh S1,2025')
                    ->maxLength(100),
                TextInput::make('program_study')
                    ->label('PROGRAM STUDI')
                    ->placeholder('Masukan Program Studi')
                    ->maxLength(150),
                Select::make('school_id')
                    ->label('ASAL MADRASAH/SEKOLAH')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => School::activeOptionsFor())
                    ->rules(fn (): array => [
                        Rule::in(array_keys(School::activeOptionsFor())),
                    ])
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),
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
                TextInput::make('nip')
                    ->label('NIP')
                    ->length(18)
                    ->regex('/^[0-9]{18}$/')
                    ->unique('employees', 'nip')
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                TextInput::make('rank')
                    ->label('PANGKAT')
                    ->maxLength(100)
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                TextInput::make('grade')
                    ->label('GOLONGAN')
                    ->maxLength(50)
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                TextInput::make('birth_place')
                    ->label('TEMPAT LAHIR')
                    ->maxLength(100),
                DatePicker::make('birth_date')
                    ->label('TANGGAL LAHIR')
                    ->maxDate(now()),
                DatePicker::make('assignment_start_date')
                    ->label('TMT (TERHITUNG MULAI TANGGAL)')
                    ->required(),
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
                TextInput::make('application_password')
                    ->label('PASSWORD BARU APLIKASI')
                    ->placeholder('Masukan Password')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->minLength(8)
                    ->required(),
                FileUpload::make('avatar_path')
                    ->label('FOTO')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('3:4')
                    ->imageResizeTargetWidth('600')
                    ->imageResizeTargetHeight('800')
                    ->imagePreviewHeight('120')
                    ->panelLayout('compact')
                    ->previewable()
                    ->openable()
                    ->disk('public')
                    ->directory('employee-avatars')
                    ->visibility('public')
                    ->maxSize(1024000),
                Select::make('decree_period')
                    ->label('PERIODE SK YAYASAN')
                    ->placeholder('-- Pilih --')
                    ->options(self::decreePeriodOptions())
                    ->native(false),
                Placeholder::make('decree_period_gap')
                    ->label('')
                    ->content(''),
                Textarea::make('address')
                    ->label('ALAMAT')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /** @return array<string, string> */
    private static function decreePeriodOptions(): array
    {
        return [
            'Januari' => 'Januari',
            'Juli' => 'Juli',
        ];
    }
}
