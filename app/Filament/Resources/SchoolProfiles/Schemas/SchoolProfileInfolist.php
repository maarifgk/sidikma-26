<?php

namespace App\Filament\Resources\SchoolProfiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil Madrasah/Sekolah')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Sekolah/Madrasah'),
                        TextEntry::make('npsn')
                            ->label('NPSN')
                            ->placeholder('-'),
                        TextEntry::make('school_level')
                            ->label('Jenjang')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('phone')
                            ->label('Nomor Telepon')
                            ->placeholder('-'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('-'),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Status Aktif')
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
