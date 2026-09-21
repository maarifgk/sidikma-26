<?php

namespace App\Filament\Resources\PaymentTypes;

use App\Filament\Resources\PaymentTypes\Pages\CreatePaymentType;
use App\Filament\Resources\PaymentTypes\Pages\EditPaymentType;
use App\Filament\Resources\PaymentTypes\Pages\ListPaymentTypes;
use App\Filament\Resources\PaymentTypes\Schemas\PaymentTypeForm;
use App\Filament\Resources\PaymentTypes\Tables\PaymentTypesTable;
use App\Models\Foundation;
use App\Models\PaymentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentTypeResource extends Resource
{
    protected static ?string $model = PaymentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    protected static ?string $slug = 'payment-types';

    public static function form(Schema $schema): Schema
    {
        return PaymentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentTypesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $foundation = Foundation::application();
        PaymentType::ensureDefaults($foundation);

        return parent::getEloquentQuery()
            ->where('foundation_id', $foundation->getKey());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTypes::route('/'),
            'create' => CreatePaymentType::route('/create'),
            'edit' => EditPaymentType::route('/{record}/edit'),
        ];
    }
}
