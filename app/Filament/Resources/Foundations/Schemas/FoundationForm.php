<?php

namespace App\Filament\Resources\Foundations\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FoundationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Yayasan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode Yayasan')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->mutateStateForValidationUsing(
                        fn (?string $state): ?string => self::normalizeCode($state),
                    )
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => self::normalizeCode($state),
                    ),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->maxLength(30)
                    ->regex('/^[0-9+\-\s().]+$/'),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
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

    private static function normalizeCode(?string $code): ?string
    {
        return filled($code) ? Str::upper(trim($code)) : null;
    }
}
