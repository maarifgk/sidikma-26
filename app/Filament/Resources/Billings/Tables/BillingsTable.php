<?php

namespace App\Filament\Resources\Billings\Tables;

use App\Models\PaymentInvoice;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BillingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('Asal Madrasah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Jenis Pembayaran')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('amount')
                    ->label('Nilai')
                    ->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === PaymentInvoice::STATUS_PAID ? 'Lunas' : 'Belum Lunas')
                    ->color(fn (string $state): string => $state === PaymentInvoice::STATUS_PAID ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
                DeleteAction::make()
                    ->label('Delete')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
            ])
            ->recordActionsColumnLabel(fn (): ?string => auth()->user() instanceof User && auth()->user()->isAdminInduk()
                ? 'Actions'
                : null)
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada pembayaran')
            ->emptyStateDescription('Pembayaran yang terhubung dengan akun akan tampil di halaman ini.')
            ->emptyStateIcon('heroicon-o-document-minus');
    }
}
