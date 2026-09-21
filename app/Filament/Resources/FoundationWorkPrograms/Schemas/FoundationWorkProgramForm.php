<?php

namespace App\Filament\Resources\FoundationWorkPrograms\Schemas;

use App\Models\FoundationWorkProgram;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FoundationWorkProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('PROGRAM KERJA')
                    ->placeholder('Masukkan program kerja')
                    ->maxLength(255)
                    ->required(),
                DatePicker::make('implementation_date')
                    ->label('TANGGAL PELAKSANAAN')
                    ->placeholder('Pilih tanggal')
                    ->native(false),
                TextInput::make('budget')
                    ->label('ANGGARAN')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Select::make('status')
                    ->label('KETERANGAN')
                    ->placeholder('-- Pilih --')
                    ->options(FoundationWorkProgram::statusOptions())
                    ->native(false)
                    ->required(),
                Textarea::make('notes')
                    ->label('CATATAN')
                    ->placeholder('Masukkan catatan')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
