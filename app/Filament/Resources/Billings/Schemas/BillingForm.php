<?php

namespace App\Filament\Resources\Billings\Schemas;

use App\Models\AcademicYear;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BillingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('USER')
                    ->placeholder('-- Pilih Akun --')
                    ->options(fn (): array => self::userOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        $set('school_id', self::defaultSchoolId($state));
                    })
                    ->required(),
                Select::make('school_id')
                    ->label('ASAL MADRASAH')
                    ->placeholder('-- Pilih Madrasah --')
                    ->options(fn (Get $get): array => self::schoolOptions($get('user_id')))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('academic_year')
                    ->label('TAHUN AJARAN')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => AcademicYear::activeOptions() + StudentEnrollment::academicYearOptions())
                    ->default(StudentEnrollment::currentAcademicYear())
                    ->searchable()
                    ->required(),
                TextInput::make('description')
                    ->label('JENIS PEMBAYARAN')
                    ->placeholder('Contoh: Pembayaran Batik Periode 2026')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('amount')
                    ->label('NILAI')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Select::make('status')
                    ->label('STATUS')
                    ->options([
                        PaymentInvoice::STATUS_UNPAID => 'Belum Lunas',
                        PaymentInvoice::STATUS_PAID => 'Lunas',
                    ])
                    ->default(PaymentInvoice::STATUS_UNPAID)
                    ->native(false)
                    ->required(),
                Select::make('payment_method')
                    ->label('METODE PEMBAYARAN')
                    ->placeholder('-- Pilih --')
                    ->options(PaymentInvoice::paymentMethodOptions())
                    ->native(false),
                DatePicker::make('due_date')
                    ->label('JATUH TEMPO')
                    ->native(false),
                Textarea::make('notes')
                    ->label('KETERANGAN')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /** @return array<int, string> */
    private static function userOptions(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                User::ROLE_ADMIN_SEKOLAH_MADRASAH,
                User::ROLE_GURU_PEGAWAI,
            ]))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user): array => [
                $user->getKey() => $user->name.' — '.$user->email,
            ])
            ->all();
    }

    /** @return array<int, string> */
    private static function schoolOptions(mixed $userId): array
    {
        if (blank($userId)) {
            return [];
        }

        $user = User::query()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $schoolIds = $user->accessibleSchoolIds();

        if ($user->employee?->school_id !== null) {
            $schoolIds->push($user->employee->school_id);
        }

        return School::query()
            ->whereKey($schoolIds->unique())
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function defaultSchoolId(mixed $userId): ?int
    {
        $options = self::schoolOptions($userId);

        return empty($options) ? null : (int) array_key_first($options);
    }
}
