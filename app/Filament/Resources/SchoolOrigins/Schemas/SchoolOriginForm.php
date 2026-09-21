<?php

namespace App\Filament\Resources\SchoolOrigins\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SchoolOriginForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('NAMA ASAL MADRASAH/SEKOLAH')
                    ->placeholder('Pilih Sekolah/Madrasah')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('description')
                    ->label('KETERANGAN')
                    ->placeholder('Masukan Keterangan Jenjang')
                    ->maxLength(255),
            ])
            ->columns(2);
    }
}
