<?php

namespace App\Filament\Resources\BatikOrders\Schemas;

use App\Models\BatikOrder;
use App\Models\BatikProduct;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class BatikOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->label('ASAL MADRASAH/SEKOLAH')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => School::activeOptionsFor())
                    ->default(fn (): ?int => array_key_first(School::activeOptionsFor()))
                    ->rules(fn (): array => [
                        Rule::in(array_keys(School::activeOptionsFor())),
                    ])
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Select::make('product_id')
                    ->label('PRODUK BATIK')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => BatikProduct::ensureDefaults(Foundation::application())
                        ->pluck('name', 'id')
                        ->all())
                    ->default(fn (): ?int => self::requestedProductId())
                    ->live()
                    ->native(false)
                    ->required(),
                TextInput::make('quantity')
                    ->label('JUMLAH PESANAN')
                    ->numeric()
                    ->minValue(0.01)
                    ->suffix(fn (Get $get): string => BatikProduct::query()
                        ->find($get('product_id'))?->size_label ?? 'meter')
                    ->required(),
                Select::make('status')
                    ->label('STATUS')
                    ->options(BatikOrder::statusOptions())
                    ->default(BatikOrder::STATUS_ORDERED)
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isAdminInduk())
                    ->dehydrated()
                    ->native(false)
                    ->required(),
                TextInput::make('recipient_name')
                    ->label('PENERIMA')
                    ->placeholder('Masukkan nama penerima')
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isAdminInduk())
                    ->maxLength(255),
            ])
            ->columns(2);
    }

    private static function requestedProductId(): ?int
    {
        $productId = filter_var(request()->query('product'), FILTER_VALIDATE_INT);

        return $productId === false ? null : $productId;
    }
}
