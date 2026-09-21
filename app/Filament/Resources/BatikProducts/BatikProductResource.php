<?php

namespace App\Filament\Resources\BatikProducts;

use App\Filament\Resources\BatikOrders\BatikOrderResource;
use App\Filament\Resources\BatikProducts\Pages\EditBatikProduct;
use App\Filament\Resources\BatikProducts\Schemas\BatikProductForm;
use App\Models\BatikProduct;
use App\Models\Foundation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BatikProductResource extends Resource
{
    protected static ?string $model = BatikProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Produk Batik';

    public static function form(Schema $schema): Schema
    {
        return BatikProductForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditBatikProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('foundation_id', Foundation::application()->getKey());
    }

    public static function getIndexUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
    ): string {
        return BatikOrderResource::getUrl(
            panel: $panel,
            isAbsolute: $isAbsolute,
            tenant: $tenant,
        );
    }
}
