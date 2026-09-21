<?php

namespace App\Filament\Resources\Foundations;

use App\Filament\RelationManagers\ApprovalRequestsRelationManager;
use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Foundations\Pages\CreateFoundation;
use App\Filament\Resources\Foundations\Pages\EditFoundation;
use App\Filament\Resources\Foundations\Pages\ListFoundations;
use App\Filament\Resources\Foundations\Schemas\FoundationForm;
use App\Filament\Resources\Foundations\Tables\FoundationsTable;
use App\Models\Foundation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FoundationResource extends Resource
{
    protected static ?string $model = Foundation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FoundationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoundationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            ApprovalRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFoundations::route('/'),
            'create' => CreateFoundation::route('/create'),
            'edit' => EditFoundation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->accessibleTo($user);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
