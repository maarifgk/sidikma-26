<?php

namespace App\Filament\Resources\FinancialReports;

use App\Filament\Resources\FinancialReports\Pages\ListFinancialReports;
use App\Filament\Resources\FinancialReports\Tables\FinancialReportsTable;
use App\Models\PaymentInvoice;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinancialReportResource extends Resource
{
    protected static ?string $model = PaymentInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Laporan Keuangan';

    protected static ?string $pluralModelLabel = 'Laporan Keuangan';

    protected static ?string $slug = 'financial-reports';

    public static function table(Table $table): Table
    {
        return FinancialReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialReports::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['employee', 'school']);
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'school',
            fn (Builder $schoolQuery): Builder => $schoolQuery
                ->accessibleTo($user)
                ->where('is_active', true),
        );
    }
}
