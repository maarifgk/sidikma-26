<?php

namespace App\Filament\Resources\DecreeCorrectionRequests;

use App\Filament\Resources\DecreeCorrectionRequests\Pages\CreateDecreeCorrectionRequest;
use App\Filament\Resources\DecreeCorrectionRequests\Pages\EditDecreeCorrectionRequest;
use App\Filament\Resources\DecreeCorrectionRequests\Pages\ListDecreeCorrectionRequests;
use App\Filament\Resources\DecreeCorrectionRequests\Pages\ViewDecreeCorrectionRequest;
use App\Filament\Resources\DecreeCorrectionRequests\Schemas\DecreeCorrectionRequestForm;
use App\Filament\Resources\DecreeCorrectionRequests\Schemas\DecreeCorrectionRequestInfolist;
use App\Filament\Resources\DecreeCorrectionRequests\Tables\DecreeCorrectionRequestsTable;
use App\Models\DecreeCorrectionRequest;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DecreeCorrectionRequestResource extends Resource
{
    protected static ?string $model = DecreeCorrectionRequest::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Perbaikan SK';

    protected static ?string $pluralModelLabel = 'Perbaikan SK';

    protected static ?string $slug = 'decree-corrections';

    public static function form(Schema $schema): Schema
    {
        return DecreeCorrectionRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DecreeCorrectionRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DecreeCorrectionRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return $user instanceof User ? parent::getEloquentQuery()->with(['school', 'submitter'])->accessibleTo($user) : parent::getEloquentQuery()->whereRaw('1=0');
    }

    public static function getNavigationBadge(): ?string
    {
        return auth()->user()?->isAdminInduk() ? (string) static::getModel()::where('status', DecreeCorrectionRequest::STATUS_SUBMITTED)->count() : null;
    }

    public static function getPages(): array
    {
        return ['index' => ListDecreeCorrectionRequests::route('/'), 'create' => CreateDecreeCorrectionRequest::route('/create'), 'view' => ViewDecreeCorrectionRequest::route('/{record}'), 'edit' => EditDecreeCorrectionRequest::route('/{record}/edit')];
    }
}
