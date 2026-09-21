<?php

namespace App\Filament\Resources\TreasuryTransactions\Tables;

use App\Models\TreasuryTransaction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class TreasuryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Uraian')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('income_category')
                    ->label('Jenis Pemasukan')
                    ->state(fn (TreasuryTransaction $record): string => $record->transaction_type === TreasuryTransaction::TYPE_INCOME
                        ? $record->categoryLabel()
                        : '-'),
                TextColumn::make('income_amount')
                    ->label('Pemasukan')
                    ->state(fn (TreasuryTransaction $record): float => $record->transaction_type === TreasuryTransaction::TYPE_INCOME
                        ? (float) $record->amount
                        : 0)
                    ->formatStateUsing(fn (mixed $state): string => self::rupiah($state)),
                TextColumn::make('expense_category')
                    ->label('Jenis Pengeluaran')
                    ->state(fn (TreasuryTransaction $record): string => $record->transaction_type === TreasuryTransaction::TYPE_EXPENSE
                        ? $record->categoryLabel()
                        : '-'),
                TextColumn::make('expense_amount')
                    ->label('Pengeluaran')
                    ->state(fn (TreasuryTransaction $record): float => $record->transaction_type === TreasuryTransaction::TYPE_EXPENSE
                        ? (float) $record->amount
                        : 0)
                    ->formatStateUsing(fn (mixed $state): string => self::rupiah($state)),
                TextColumn::make('receipt_action')
                    ->label('Bukti Transaksi')
                    ->state(fn (TreasuryTransaction $record): string => filled($record->receipt_path)
                        ? 'View Bukti'
                        : 'Belum Ada Bukti')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'View Bukti' ? 'primary' : 'gray')
                    ->url(fn (TreasuryTransaction $record): ?string => filled($record->receipt_path)
                        ? Storage::disk(TreasuryTransaction::DISK)
                            ->temporaryUrl($record->receipt_path, now()->addMinutes(5))
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->color('warning'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->color('danger'),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada data bendahara')
            ->emptyStateDescription('Gunakan tombol Input Pemasukan atau Input Pengeluaran untuk menambah transaksi.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    private static function rupiah(mixed $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
