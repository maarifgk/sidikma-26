<?php

namespace App\Filament\Resources\EmployeeMutations;

use App\Filament\Resources\EmployeeMutations\Pages\CreateEmployeeMutation;
use App\Filament\Resources\EmployeeMutations\Pages\ListEmployeeMutations;
use App\Filament\Resources\EmployeeMutations\Schemas\EmployeeMutationForm;
use App\Filament\Resources\EmployeeMutations\Tables\EmployeeMutationsTable;
use App\Models\EmployeeMutation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeMutationResource extends Resource
{
    protected static ?string $model = EmployeeMutation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Usulan Mutasi';

    protected static ?string $pluralModelLabel = 'Usulan Mutasi';

    public static function form(Schema $schema): Schema
    {
        return EmployeeMutationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeMutationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['originSchool', 'destinationSchool']);
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdminInduk()) {
            return $query;
        }

        $schoolIds = $user->accessibleSchoolIds();

        return $query->where(function (Builder $query) use ($schoolIds, $user): void {
            $query
                ->whereIn('origin_school_id', $schoolIds)
                ->orWhereIn('destination_school_id', $schoolIds)
                ->orWhere('submitted_by', $user->getKey());
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeMutations::route('/'),
            'create' => CreateEmployeeMutation::route('/create'),
        ];
    }
}
