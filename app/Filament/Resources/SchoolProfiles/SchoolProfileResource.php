<?php

namespace App\Filament\Resources\SchoolProfiles;

use App\Filament\Resources\SchoolProfiles\Pages\ListSchoolProfiles;
use App\Filament\Resources\SchoolProfiles\Pages\ViewSchoolProfile;
use App\Filament\Resources\SchoolProfiles\RelationManagers\SchoolProfileEmployeesRelationManager;
use App\Filament\Resources\SchoolProfiles\Schemas\SchoolProfileInfolist;
use App\Filament\Resources\SchoolProfiles\Tables\SchoolProfilesTable;
use App\Models\School;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolProfileResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Profil Madrasah/Sekolah';

    protected static ?string $pluralModelLabel = 'Profil Madrasah/Sekolah';

    protected static ?string $slug = 'school-profiles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return SchoolProfilesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SchoolProfileInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolProfiles::route('/'),
            'view' => ViewSchoolProfile::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SchoolProfileEmployeesRelationManager::class,
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
            ->accessibleTo($user);
    }
}
