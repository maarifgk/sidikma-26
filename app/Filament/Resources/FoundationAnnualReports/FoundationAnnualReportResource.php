<?php

namespace App\Filament\Resources\FoundationAnnualReports;

use App\Filament\Resources\FoundationAnnualReports\Pages\CreateFoundationAnnualReport;
use App\Filament\Resources\FoundationAnnualReports\Pages\EditFoundationAnnualReport;
use App\Filament\Resources\FoundationAnnualReports\Pages\ListFoundationAnnualReports;
use App\Filament\Resources\FoundationAnnualReports\Schemas\FoundationAnnualReportForm;
use App\Filament\Resources\FoundationAnnualReports\Tables\FoundationAnnualReportsTable;
use App\Models\Foundation;
use App\Models\FoundationAnnualReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FoundationAnnualReportResource extends Resource
{
    protected static ?string $model = FoundationAnnualReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Laporan Tahunan';

    protected static ?string $pluralModelLabel = 'Laporan Tahunan';

    public static function form(Schema $schema): Schema
    {
        return FoundationAnnualReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoundationAnnualReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFoundationAnnualReports::route('/'),
            'create' => CreateFoundationAnnualReport::route('/create'),
            'edit' => EditFoundationAnnualReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('foundation_id', Foundation::application()->getKey());
    }
}
