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

        return $user->isAdminInduk()
            ? $query
            : $query->whereIn('school_id', $user->accessibleSchoolIds());
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
