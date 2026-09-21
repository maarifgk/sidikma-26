<?php

namespace App\Filament\Resources\InvoiceSchools;

use App\Filament\Resources\InvoiceSchools\Pages\ListInvoiceSchools;
use App\Filament\Resources\InvoiceSchools\Pages\ViewInvoiceSchool;
use App\Filament\Resources\InvoiceSchools\Tables\InvoiceSchoolsTable;
use App\Models\School;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceSchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Invoice Pembayaran';

    protected static ?string $pluralModelLabel = 'Invoice Pembayaran';

    protected static ?string $slug = 'invoices';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return InvoiceSchoolsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoiceSchools::route('/'),
            'view' => ViewInvoiceSchool::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->accessibleTo($user)
            ->where('is_active', true);
    }
}
