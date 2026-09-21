<?php

namespace App\Filament\Resources\EmployeePositions\Schemas;

use App\Models\EmployeePosition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeePositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Jabatan')
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
                TextInput::make('name')
                    ->label('Nama Posisi/Jabatan')
                    ->required()
                    ->maxLength(255),
                Select::make('category')
                    ->label('Kategori')
                    ->options(self::categoryOptions())
                    ->required()
                    ->native(false),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return [
            EmployeePosition::CATEGORY_TEACHING => 'Pengajar',
            EmployeePosition::CATEGORY_STRUCTURAL => 'Struktural',
            EmployeePosition::CATEGORY_ADMINISTRATIVE => 'Administrasi',
            EmployeePosition::CATEGORY_SUPPORT => 'Pendukung',
        ];
    }

    private static function normalizeCode(?string $code): ?string
    {
        return filled($code) ? strtoupper(trim($code)) : null;
    }
}
