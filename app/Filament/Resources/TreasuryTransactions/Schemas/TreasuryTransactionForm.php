<?php

namespace App\Filament\Resources\TreasuryTransactions\Schemas;

use App\Models\TreasuryTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TreasuryTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('transaction_type')
                    ->default(fn (): string => self::requestedType()),
                DatePicker::make('transaction_date')
                    ->label('TANGGAL')
                    ->required(),
                TextInput::make('description')
                    ->label('URAIAN')
                    ->maxLength(2000)
                    ->required(),
                Select::make('category')
                    ->label(fn (Get $get): string => self::isExpense($get)
                        ? 'JENIS PENGELUARAN'
                        : 'JENIS PEMASUKAN')
                    ->placeholder(fn (Get $get): string => self::isExpense($get)
                        ? '-- Pilih Jenis Pengeluaran --'
                        : '-- Pilih Jenis Pemasukan --')
                    ->options(fn (Get $get): array => TreasuryTransaction::categoryOptions(
                        $get('transaction_type') ?: self::requestedType(),
                    ))
                    ->native(false)
                    ->required(),
                TextInput::make('amount')
                    ->label(fn (Get $get): string => self::isExpense($get)
                        ? 'JUMLAH PENGELUARAN'
                        : 'JUMLAH PEMASUKAN')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                FileUpload::make('receipt_path')
                    ->label('BUKTI TRANSAKSI')
                    ->disk(TreasuryTransaction::DISK)
                    ->visibility('private')
                    ->directory('receipts')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                    ])
                    ->maxSize(1024000)
                    ->downloadable()
                    ->openable()
                    ->previewable(false),
            ])
            ->columns(1);
    }

    private static function isExpense(Get $get): bool
    {
        return ($get('transaction_type') ?: self::requestedType()) === TreasuryTransaction::TYPE_EXPENSE;
    }

    private static function requestedType(): string
    {
        $type = request()->query('type', TreasuryTransaction::TYPE_INCOME);

        return in_array($type, [TreasuryTransaction::TYPE_INCOME, TreasuryTransaction::TYPE_EXPENSE], true)
            ? $type
            : TreasuryTransaction::TYPE_INCOME;
    }
}
