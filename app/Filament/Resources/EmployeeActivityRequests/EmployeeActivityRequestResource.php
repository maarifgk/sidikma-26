<?php

namespace App\Filament\Resources\EmployeeActivityRequests;

use App\Filament\Resources\EmployeeActivityRequests\Pages\CreateEmployeeActivityRequest;
use App\Filament\Resources\EmployeeActivityRequests\Pages\ListEmployeeActivityRequests;
use App\Filament\Resources\EmployeeActivityRequests\Schemas\EmployeeActivityRequestForm;
use App\Filament\Resources\EmployeeActivityRequests\Tables\EmployeeActivityRequestsTable;
use App\Models\EmployeeActivityRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeActivityRequestResource extends Resource
{
    protected static ?string $model = EmployeeActivityRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pengajuan Penonaktifan';

    protected static ?string $pluralModelLabel = 'Pengajuan Penonaktifan';

    public static function form(Schema $schema): Schema
    {
        return EmployeeActivityRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeActivityRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('school');
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $user->isAdminInduk()
            ? $query
            : $query->whereIn('school_id', $user->accessibleSchoolIds());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeActivityRequests::route('/'),
            'create' => CreateEmployeeActivityRequest::route('/create'),
        ];
    }
}
