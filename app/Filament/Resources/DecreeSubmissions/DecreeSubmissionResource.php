<?php

namespace App\Filament\Resources\DecreeSubmissions;

use App\Filament\Resources\DecreeSubmissions\Pages\CreateDecreeSubmission;
use App\Filament\Resources\DecreeSubmissions\Pages\EditDecreeSubmission;
use App\Filament\Resources\DecreeSubmissions\Pages\ListDecreeSubmissions;
use App\Filament\Resources\DecreeSubmissions\Pages\ViewDecreeSubmission;
use App\Filament\Resources\DecreeSubmissions\Schemas\DecreeSubmissionForm;
use App\Filament\Resources\DecreeSubmissions\Schemas\DecreeSubmissionInfolist;
use App\Filament\Resources\DecreeSubmissions\Tables\DecreeSubmissionsTable;
use App\Models\DecreeSubmission;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DecreeSubmissionResource extends Resource
{
    protected static ?string $model = DecreeSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pengajuan SK';

    protected static ?string $pluralModelLabel = 'Pengajuan SK';

    public static function form(Schema $schema): Schema
    {
        return DecreeSubmissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DecreeSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DecreeSubmissionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['school', 'employee', 'type', 'submittedBy']);

        return $user instanceof User ? $query->accessibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDecreeSubmissions::route('/'),
            'create' => CreateDecreeSubmission::route('/create'),
            'view' => ViewDecreeSubmission::route('/{record}'),
            'edit' => EditDecreeSubmission::route('/{record}/edit'),
        ];
    }
}
