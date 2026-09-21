<?php

namespace App\Filament\Resources\Schools\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SchoolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Sekolah/Madrasah')
                    ->required()
                    ->maxLength(255),
                TextInput::make('npsn')
                    ->label('NPSN')
                    ->required()
                    ->minLength(8)
                    ->maxLength(8)
                    ->regex('/^[0-9]{8}$/')
                    ->unique(ignoreRecord: true)
                    ->mutateStateForValidationUsing(
                        fn (?string $state): ?string => self::normalizeNpsn($state),
                    )
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => self::normalizeNpsn($state),
                    ),
                Select::make('school_level')
                    ->label('Jenjang')
                    ->options([
                        'MI' => 'MI',
                        'MTs' => 'MTs',
                        'SMP' => 'SMP',
                    ])
                    ->required()
                    ->native(false),
                Select::make('accreditation_status')
                    ->label('Status Akreditasi')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C' => 'C',
                        'Belum Terakreditasi' => 'Belum Terakreditasi',
                    ])
                    ->searchable()
                    ->native(false),
                TextInput::make('accreditation_expiry_year')
                    ->label('Masa Akreditasi Sampai Tahun')
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(2200),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Select::make('land_status')
                    ->label('Status Tanah')
                    ->options([
                        'Wakaf' => 'Wakaf',
                        'Hak Milik' => 'Hak Milik',
                        'Sewa' => 'Sewa',
                        'Pinjam Pakai' => 'Pinjam Pakai',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->searchable()
                    ->native(false),
                TextInput::make('land_area')
                    ->label('Luas Tanah')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('m²'),
                Select::make('has_land_certificate')
                    ->label('Keterangan Sertifikat Tanah')
                    ->placeholder('Pilih Sudah atau Belum')
                    ->options([
                        '1' => 'Sudah',
                        '0' => 'Belum',
                    ])
                    ->native(false),
                Select::make('has_bhpnu_ownership')
                    ->label('Keterangan BHPNU')
                    ->placeholder('Pilih Sudah atau Belum')
                    ->options([
                        '1' => 'Sudah',
                        '0' => 'Belum',
                    ])
                    ->native(false),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
            ])
            ->columns(2);
    }

    private static function normalizeNpsn(?string $npsn): ?string
    {
        return filled($npsn) ? trim($npsn) : null;
    }
}
