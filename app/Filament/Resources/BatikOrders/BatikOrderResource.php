<?php

namespace App\Filament\Resources\BatikOrders;

use App\Filament\Resources\BatikOrders\Pages\CreateBatikOrder;
use App\Filament\Resources\BatikOrders\Pages\ListBatikOrders;
use App\Filament\Resources\BatikOrders\Schemas\BatikOrderForm;
use App\Filament\Resources\BatikOrders\Tables\BatikOrdersTable;
use App\Models\BatikOrder;
use App\Models\Foundation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BatikOrderResource extends Resource
{
    protected static ?string $model = BatikOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pesanan Batik';

    protected static ?string $pluralModelLabel = 'Pesanan Batik';

    public static function form(Schema $schema): Schema
    {
        return BatikOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BatikOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatikOrders::route('/'),
            'create' => CreateBatikOrder::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('foundation_id', Foundation::application()->getKey())
            ->with(['product', 'school']);

        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $user->isAdminInduk()
            ? $query
            : $query->whereIn('school_id', $user->accessibleSchoolIds());
    }
}
