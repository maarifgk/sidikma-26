<?php

namespace App\Filament\Resources\ApprovalRequests;

use App\Filament\Resources\ApprovalRequests\Pages\ListApprovalRequests;
use App\Filament\Resources\ApprovalRequests\Pages\ViewApprovalRequest;
use App\Filament\Resources\ApprovalRequests\Schemas\ApprovalRequestInfolist;
use App\Filament\Resources\ApprovalRequests\Tables\ApprovalRequestsTable;
use App\Models\ApprovalRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovalRequestResource extends Resource
{
    protected static ?string $model = ApprovalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Approval';

    protected static ?string $pluralModelLabel = 'Daftar Approval';

    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Schema $schema): Schema
    {
        return ApprovalRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApprovalRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalRequests::route('/'),
            'view' => ViewApprovalRequest::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
