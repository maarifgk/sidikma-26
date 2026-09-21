<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\School;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('avatar_path')
                    ->label('Foto Guru/Pegawai')
                    ->disk('public')
                    ->visibility('public')
                    ->imageWidth(240)
                    ->imageHeight(320)
                    ->extraImgAttributes([
                        'class' => 'rounded-xl object-cover',
                        'style' => 'aspect-ratio: 3 / 4;',
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'view')
                    ->columnSpanFull(),
                FileUpload::make('avatar_path')
                    ->label('Foto Guru/Pegawai')
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
                    ->maxSize(1024000)
                    ->hidden(fn (string $operation): bool => $operation === 'view')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nik')
                    ->label('NIK')
                    ->length(16)
                    ->regex('/^[0-9]{16}$/')
                    ->unique(ignoreRecord: true)
                    ->mutateStateForValidationUsing(
                        fn (?string $state): ?string => self::normalizeIdentifier($state),
                    )
                    ->dehydrateStateUsing(fn (?string $state): ?string => self::normalizeIdentifier($state)),
                TextInput::make('employee_code')
                    ->label('EWANUGK')
                    ->required()
                    ->maxLength(50)
                    ->regex('/^[A-Z0-9._\/-]+$/')
                    ->unique(ignoreRecord: true)
                    ->mutateStateForValidationUsing(
                        fn (?string $state): ?string => self::normalizeCode($state),
                    )
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => self::normalizeCode($state),
                    ),
                TextInput::make('nuptk')
                    ->label('NUPTK/NPK')
                    ->maxLength(30)
                    ->regex('/^[A-Z0-9._\/-]+$/i')
                    ->unique(ignoreRecord: true)
                    ->mutateStateForValidationUsing(
                        fn (?string $state): ?string => self::normalizeCode($state),
                    )
                    ->dehydrateStateUsing(fn (?string $state): ?string => self::normalizeCode($state)),
                TextInput::make('birth_place')
                    ->label('Tempat Lahir')
                    ->maxLength(100),
                DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->maxDate(now()),
                Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        Employee::GENDER_MALE => 'Laki-laki',
                        Employee::GENDER_FEMALE => 'Perempuan',
                    ])
                    ->native(false),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->maxLength(30)
                    ->regex('/^[0-9+\-\s().]+$/'),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->mutateStateForValidationUsing(
                        fn (?string $state): ?string => self::normalizeEmail($state),
                    )
                    ->dehydrateStateUsing(fn (?string $state): ?string => self::normalizeEmail($state)),
                Select::make('school_id')
                    ->label('Sekolah/Madrasah')
                    ->options(fn (): array => School::query()
                        ->when(
                            auth()->user() instanceof User,
                            fn (Builder $query): Builder => $query->accessibleTo(auth()->user()),
                        )
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->nullable()
                    ->native(false),
                Select::make('employment_status')
                    ->label('Status Kepegawaian')
                    ->options(function (?Employee $record): array {
                        $options = Employee::employmentStatusOptions();

                        if (filled($record?->employment_status)
                            && ! array_key_exists($record->employment_status, $options)) {
                            $options[$record->employment_status] = Employee::employmentStatusLabel($record->employment_status);
                        }

                        return $options;
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if (! Employee::isPnsStatus($state)) {
                            $set('nip', null);
                            $set('rank', null);
                            $set('grade', null);
                        }
                    })
                    ->native(false),
                Select::make('position_id')
                    ->label('Ketugasan')
                    ->placeholder('-- Pilih --')
                    ->options(fn (?Employee $record): array => EmployeePosition::query()
                        ->where(function (Builder $query) use ($record): void {
                            $query->where('is_active', true);

                            if (filled($record?->currentAssignment?->employee_position_id)) {
                                $query->orWhere(
                                    $query->getModel()->getQualifiedKeyName(),
                                    $record->currentAssignment->employee_position_id,
                                );
                            }
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false),
                TextInput::make('nip')
                    ->label('NIP')
                    ->length(18)
                    ->regex('/^[0-9]{18}$/')
                    ->unique(ignoreRecord: true)
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status')))
                    ->mutateStateForValidationUsing(
                        fn (?string $state): ?string => self::normalizeIdentifier($state),
                    )
                    ->dehydrateStateUsing(fn (?string $state): ?string => self::normalizeIdentifier($state)),
                TextInput::make('rank')
                    ->label('Pangkat')
                    ->maxLength(100)
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                TextInput::make('grade')
                    ->label('Golongan')
                    ->maxLength(50)
                    ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
                TextInput::make('last_education')
                    ->label('Pendidikan Terakhir dan Tahun Lulus')
                    ->placeholder('Contoh: S1, 2025')
                    ->maxLength(100),
                TextInput::make('program_study')
                    ->label('Program Studi')
                    ->maxLength(150),
                Select::make('decree_period')
                    ->label('Periode SK Yayasan')
                    ->placeholder('-- Pilih --')
                    ->options(self::decreePeriodOptions())
                    ->native(false),
                Select::make('user_id')
                    ->label('Akun User')
                    ->options(fn (?Employee $record): array => self::availableUserOptions($record))
                    ->searchable()
                    ->preload()
                    ->unique(ignoreRecord: true)
                    ->nullable()
                    ->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)
                    ->native(false),
                TextInput::make('new_account_password')
                    ->label('Password Akun Baru SIDIKMA')
                    ->placeholder('Isi hanya jika ingin mengganti password')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
            ])
            ->columns(2);
    }

    /**
     * @return array<int, string>
     */
    private static function availableUserOptions(?Employee $record): array
    {
        return User::query()
            ->where(function (Builder $query) use ($record): void {
                $query->whereDoesntHave('employee');

                if (filled($record?->user_id)) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $record->user_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user): array => [
                $user->getKey() => "{$user->name} ({$user->email})",
            ])
            ->all();
    }

    private static function normalizeCode(?string $code): ?string
    {
        return filled($code) ? strtoupper(trim($code)) : null;
    }

    private static function normalizeIdentifier(?string $identifier): ?string
    {
        return filled($identifier) ? trim($identifier) : null;
    }

    private static function normalizeEmail(?string $email): ?string
    {
        return filled($email) ? strtolower(trim($email)) : null;
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
