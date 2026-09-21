<?php

namespace App\Filament\Resources\CorrespondenceRequests;

use App\Filament\Resources\CorrespondenceRequests\Pages\CreateCorrespondenceRequest;
use App\Filament\Resources\CorrespondenceRequests\Pages\ListCorrespondenceRequests;
use App\Filament\Resources\CorrespondenceRequests\Schemas\CorrespondenceRequestForm;
use App\Filament\Resources\CorrespondenceRequests\Tables\CorrespondenceRequestsTable;
use App\Models\CorrespondenceRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CorrespondenceRequestResource extends Resource
{
    protected static ?string $model = CorrespondenceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pengajuan Persuratan';

    protected static ?string $pluralModelLabel = 'Pengajuan Persuratan';

    protected static ?string $recordTitleAttribute = 'type_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['school_name', 'type_name', 'notes'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->type_name.' — '.$record->school_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Status' => CorrespondenceRequest::statusOptions()[$record->process_status] ?? $record->process_status];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl(parameters: ['tableSearch' => $record->school_name]);
    }

    public static function form(Schema $schema): Schema
    {
        return CorrespondenceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CorrespondenceRequestsTable::configure($table);
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
            'index' => ListCorrespondenceRequests::route('/'),
            'create' => CreateCorrespondenceRequest::route('/create'),
        ];
    }
}
