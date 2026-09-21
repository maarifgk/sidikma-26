<?php

namespace App\Filament\Resources\EducatorRecaps;

use App\Filament\Resources\EducatorRecaps\Pages\CreateEducatorRecap;
use App\Filament\Resources\EducatorRecaps\Pages\EditEducatorRecap;
use App\Filament\Resources\EducatorRecaps\Pages\ListEducatorRecaps;
use App\Filament\Resources\EducatorRecaps\Schemas\EducatorRecapForm;
use App\Filament\Resources\EducatorRecaps\Tables\EducatorRecapsTable;
use App\Models\EducatorRecap;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EducatorRecapResource extends Resource
{
    protected static ?string $model = EducatorRecap::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Data Jumlah Tenaga Pendidik';

    protected static ?string $pluralModelLabel = 'Data Jumlah Tenaga Pendidik';

    public static function form(Schema $schema): Schema
    {
        return EducatorRecapForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EducatorRecapsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEducatorRecaps::route('/'),
            'create' => CreateEducatorRecap::route('/create'),
            'edit' => EditEducatorRecap::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
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
