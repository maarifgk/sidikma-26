<?php

namespace App\Filament\Resources\FinancialReports\Tables;

use App\Models\PaymentInvoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinancialReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('report_name')
                    ->label('Nama')
                    ->state(fn (PaymentInvoice $record): string => $record->employee?->name ?? $record->school?->name ?? '-')
                    ->searchable(query: fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                        $normalizedSearch = '%'.mb_strtolower($search).'%';

                        $query
                            ->whereHas('employee', fn ($query) => $query->whereRaw(
                                'LOWER(name) LIKE ?',
                                [$normalizedSearch],
                            ))
                            ->orWhereHas('school', fn ($query) => $query->whereRaw(
                                'LOWER(name) LIKE ?',
                                [$normalizedSearch],
                            ));
                    })),
                TextColumn::make('academic_year')
                    ->label('Tahun')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Jenis Pembayaran')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('amount')
                    ->label('Nilai')
                    ->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->formatStateUsing(fn (PaymentInvoice $record): string => $record->paymentMethodLabel()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === PaymentInvoice::STATUS_PAID ? 'Lunas' : 'Belum Lunas')
                    ->color(fn (string $state): string => $state === PaymentInvoice::STATUS_PAID ? 'success' : 'warning'),
                TextColumn::make('notes')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada data laporan keuangan')
            ->emptyStateDescription('Transaksi pembayaran akan tampil pada tabel ini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
