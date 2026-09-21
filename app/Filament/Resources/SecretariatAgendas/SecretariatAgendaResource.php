<?php

namespace App\Filament\Resources\SecretariatAgendas;

use App\Filament\Resources\SecretariatAgendas\Pages\CreateSecretariatAgenda;
use App\Filament\Resources\SecretariatAgendas\Pages\ListSecretariatAgendas;
use App\Filament\Resources\SecretariatAgendas\Schemas\SecretariatAgendaForm;
use App\Filament\Resources\SecretariatAgendas\Tables\SecretariatAgendasTable;
use App\Models\Foundation;
use App\Models\SecretariatAgenda;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecretariatAgendaResource extends Resource
{
    protected static ?string $model = SecretariatAgenda::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Agenda Kesekretariatan';

    protected static ?string $pluralModelLabel = 'Agenda Kesekretariatan';

    public static function form(Schema $schema): Schema
    {
        return SecretariatAgendaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SecretariatAgendasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecretariatAgendas::route('/'),
            'create' => CreateSecretariatAgenda::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('foundation_id', Foundation::application()->getKey());
    }
}
