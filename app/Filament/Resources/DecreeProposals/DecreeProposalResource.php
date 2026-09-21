<?php

namespace App\Filament\Resources\DecreeProposals;

use App\Filament\Resources\DecreeProposals\Pages\CreateDecreeProposal;
use App\Filament\Resources\DecreeProposals\Pages\ListDecreeProposals;
use App\Filament\Resources\DecreeProposals\Schemas\DecreeProposalForm;
use App\Filament\Resources\DecreeProposals\Schemas\DecreeProposalInfolist;
use App\Filament\Resources\DecreeProposals\Tables\DecreeProposalsTable;
use App\Models\DecreeProposal;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DecreeProposalResource extends Resource
{
    protected static ?string $model = DecreeProposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Usulan SK Baru';

    protected static ?string $pluralModelLabel = 'Usulan SK Baru';

    public static function form(Schema $schema): Schema
    {
        return DecreeProposalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DecreeProposalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DecreeProposalsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('employee.school');
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdminInduk()) {
            return $query;
        }

        return $query->whereHas(
            'employee',
            fn (Builder $employeeQuery): Builder => $employeeQuery
                ->whereIn('school_id', $user->accessibleSchoolIds()),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDecreeProposals::route('/'),
            'create' => CreateDecreeProposal::route('/create'),
        ];
    }
}
