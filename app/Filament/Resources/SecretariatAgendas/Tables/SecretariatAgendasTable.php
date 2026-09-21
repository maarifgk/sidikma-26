<?php

namespace App\Filament\Resources\SecretariatAgendas\Tables;

use App\Models\SecretariatAgenda;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class SecretariatAgendasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('activity')
                    ->label('Kegiatan')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('implementation_date')
                    ->label('Tanggal Pelaksanaan')
                    ->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)
                        ->locale('id')
                        ->translatedFormat('d F Y'))
                    ->sortable(),
                TextColumn::make('officer')
                    ->label('Petugas')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Keterangan')
                    ->formatStateUsing(
                        fn (string $state): string => SecretariatAgenda::statusOptions()[$state] ?? $state,
                    )
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Delete')
                    ->color('danger'),
            ])
            ->defaultSort('implementation_date', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada agenda kesekretariatan')
            ->emptyStateDescription('Klik Add untuk menambahkan agenda baru.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
