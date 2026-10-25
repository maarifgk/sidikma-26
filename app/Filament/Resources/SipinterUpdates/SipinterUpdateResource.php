<?php

namespace App\Filament\Resources\SipinterUpdates;

use App\Filament\Resources\SipinterUpdates\Pages\CreateSipinterUpdate;
use App\Filament\Resources\SipinterUpdates\Pages\EditSipinterUpdate;
use App\Filament\Resources\SipinterUpdates\Pages\ListSipinterUpdates;
use App\Filament\Resources\SipinterUpdates\Schemas\SipinterUpdateForm;
use App\Filament\Resources\SipinterUpdates\Tables\SipinterUpdatesTable;
use App\Models\SipinterUpdate;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SipinterUpdateResource extends Resource
{
    protected static ?string $model = SipinterUpdate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Update Data Sipinter';

    protected static ?string $pluralModelLabel = 'Update Data Sipinter';

    protected static ?string $recordTitleAttribute = 'school.name';

    public static function form(Schema $schema): Schema
    {
        return SipinterUpdateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SipinterUpdatesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('school');
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdminInduk()) {
            return $query;
        }

        $schoolIds = $user->accessibleSchoolIds();

        // Legacy SIPINTER rows are linked to the employee's school, while
        // some accounts only expose that school through their employee link.
        if ($user->employee?->school_id !== null) {
            $schoolIds->push($user->employee->school_id);
        }

        return $query->whereIn('school_id', $schoolIds->unique()->values());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSipinterUpdates::route('/'),
            'create' => CreateSipinterUpdate::route('/create'),
            'edit' => EditSipinterUpdate::route('/{record}/edit'),
        ];
    }
}
