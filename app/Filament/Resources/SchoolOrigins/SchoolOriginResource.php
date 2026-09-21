<?php

namespace App\Filament\Resources\SchoolOrigins;

use App\Filament\Resources\SchoolOrigins\Pages\CreateSchoolOrigin;
use App\Filament\Resources\SchoolOrigins\Pages\EditSchoolOrigin;
use App\Filament\Resources\SchoolOrigins\Pages\ListSchoolOrigins;
use App\Filament\Resources\SchoolOrigins\Schemas\SchoolOriginForm;
use App\Filament\Resources\SchoolOrigins\Tables\SchoolOriginsTable;
use App\Models\SchoolOrigin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchoolOriginResource extends Resource
{
    protected static ?string $model = SchoolOrigin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Asal Madrasah';

    protected static ?string $pluralModelLabel = 'Asal Madrasah';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SchoolOriginForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolOriginsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolOrigins::route('/'),
            'create' => CreateSchoolOrigin::route('/create'),
            'edit' => EditSchoolOrigin::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
