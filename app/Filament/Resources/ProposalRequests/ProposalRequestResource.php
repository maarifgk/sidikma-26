<?php

namespace App\Filament\Resources\ProposalRequests;

use App\Filament\Resources\ProposalRequests\Pages\CreateProposalRequest;
use App\Filament\Resources\ProposalRequests\Pages\ListProposalRequests;
use App\Filament\Resources\ProposalRequests\Schemas\ProposalRequestForm;
use App\Filament\Resources\ProposalRequests\Tables\ProposalRequestsTable;
use App\Models\ProposalRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProposalRequestResource extends Resource
{
    protected static ?string $model = ProposalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pengajuan Proposal';

    protected static ?string $pluralModelLabel = 'Pengajuan Proposal';

    protected static ?string $recordTitleAttribute = 'proposal_type';

    public static function getGloballySearchableAttributes(): array
    {
        return ['school_name', 'proposal_type', 'bank_name', 'bank_account_number', 'description', 'notes'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->proposal_type.' — '.$record->school_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nominal' => 'Rp '.number_format((float) $record->requested_amount, 0, ',', '.'),
            'Status' => ProposalRequest::statusOptions()[$record->process_status] ?? $record->process_status,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl(parameters: ['tableSearch' => $record->school_name]);
    }

    public static function form(Schema $schema): Schema
    {
        return ProposalRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProposalRequestsTable::configure($table);
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
            'index' => ListProposalRequests::route('/'),
            'create' => CreateProposalRequest::route('/create'),
        ];
    }
}
