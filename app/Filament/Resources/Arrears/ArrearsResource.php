<?php

namespace App\Filament\Resources\Arrears;

use App\Filament\Resources\Arrears\Pages\ListArrears;
use App\Filament\Resources\Billings\BillingResource;
use App\Filament\Resources\Billings\Tables\BillingsTable;
use App\Models\PaymentInvoice;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArrearsResource extends Resource
{
    protected static ?string $model = PaymentInvoice::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    protected static ?string $modelLabel = 'Tunggakan';

    protected static ?string $pluralModelLabel = 'Tunggakan';

    protected static ?string $slug = 'tunggakan';

    public static function getEloquentQuery(): Builder
    {
        return BillingResource::getEloquentQuery()->where('status', PaymentInvoice::STATUS_UNPAID);
    }

    public static function table(Table $table): Table
    {
        return BillingsTable::configure($table, withActions: false)
            ->recordActionsColumnLabel(null)
            ->recordUrl(null)
            ->contentFooter(null)
            ->filters([
                SelectFilter::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->options(fn (): array => static::getEloquentQuery()
                        ->whereNotNull('academic_year')->distinct()->orderByDesc('academic_year')
                        ->pluck('academic_year', 'academic_year')->all()),
            ])
            ->emptyStateHeading('Tidak ada tunggakan')
            ->emptyStateDescription('Tidak ada tagihan belum lunas yang sesuai dengan pencarian dan hak akses Anda.');
    }

    public static function getPages(): array
    {
        return ['index' => ListArrears::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
