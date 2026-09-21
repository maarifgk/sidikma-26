<?php

namespace App\Filament\Resources\AcademicYears\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicYearsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'ON' : 'OFF')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->color('success'),
                DeleteAction::make()
                    ->label('Delete')
                    ->color('danger'),
            ])
            ->recordActionsColumnLabel('Actions')
            ->defaultSort('name', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada tahun ajaran')
            ->emptyStateDescription('Klik Add untuk menambahkan tahun ajaran baru.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
