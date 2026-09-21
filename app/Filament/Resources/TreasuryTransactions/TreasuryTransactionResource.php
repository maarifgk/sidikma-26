<?php

namespace App\Filament\Resources\TreasuryTransactions;

use App\Filament\Resources\TreasuryTransactions\Pages\CreateTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\EditTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\ListTreasuryTransactions;
use App\Filament\Resources\TreasuryTransactions\Schemas\TreasuryTransactionForm;
use App\Filament\Resources\TreasuryTransactions\Tables\TreasuryTransactionsTable;
use App\Models\Foundation;
use App\Models\TreasuryTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TreasuryTransactionResource extends Resource
{
    protected static ?string $model = TreasuryTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Data Bendahara';

    protected static ?string $pluralModelLabel = 'Data Bendahara';

    public static function form(Schema $schema): Schema
    {
        return TreasuryTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreasuryTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTreasuryTransactions::route('/'),
            'create' => CreateTreasuryTransaction::route('/create'),
            'edit' => EditTreasuryTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('foundation_id', Foundation::application()->getKey());
    }
}
