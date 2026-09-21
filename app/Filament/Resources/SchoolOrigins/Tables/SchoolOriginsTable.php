<?php

namespace App\Filament\Resources\SchoolOrigins\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SchoolOriginsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Nama Asal Madrasah/Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada data asal madrasah')
            ->emptyStateDescription('Nama asal madrasah atau sekolah akan tampil di halaman ini.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}
