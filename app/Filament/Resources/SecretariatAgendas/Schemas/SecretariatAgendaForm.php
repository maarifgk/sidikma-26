<?php

namespace App\Filament\Resources\SecretariatAgendas\Schemas;

use App\Models\SecretariatAgenda;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SecretariatAgendaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('activity')
                    ->label('KEGIATAN')
                    ->placeholder('Masukkan nama atau uraian kegiatan')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('implementation_date')
                    ->label('TANGGAL PELAKSANAAN')
                    ->required(),
                TextInput::make('officer')
                    ->label('PETUGAS')
                    ->placeholder('Masukkan nama petugas')
                    ->maxLength(255)
                    ->required(),
                Select::make('status')
                    ->label('KETERANGAN')
                    ->placeholder('-- Pilih --')
                    ->options(SecretariatAgenda::statusOptions())
                    ->native(false)
                    ->required(),
                Textarea::make('notes')
                    ->label('CATATAN')
                    ->placeholder('Masukkan catatan')
                    ->rows(3),
            ])
            ->columns(2);
    }
}
