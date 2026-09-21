<?php

namespace App\Filament\Resources\PaymentTypes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Pembayaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'ON' : 'OFF')
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
            ->defaultSort('id')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada jenis pembayaran')
            ->emptyStateDescription('Klik Add untuk menambahkan jenis pembayaran baru.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
