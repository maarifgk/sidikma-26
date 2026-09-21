<?php

namespace App\Filament\Resources\SchoolHeads;

use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\SchoolHeads\Pages\CreateSchoolHead;
use App\Filament\Resources\SchoolHeads\Pages\EditSchoolHead;
use App\Filament\Resources\SchoolHeads\Pages\ListSchoolHeads;
use App\Filament\Resources\SchoolHeads\Pages\ViewSchoolHead;
use App\Filament\Resources\SchoolHeads\Schemas\SchoolHeadForm;
use App\Filament\Resources\SchoolHeads\Schemas\SchoolHeadInfolist;
use App\Filament\Resources\SchoolHeads\Tables\SchoolHeadsTable;
use App\Models\SchoolHead;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolHeadResource extends Resource
{
    protected static ?string $model = SchoolHead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Kepala Madrasah/Sekolah';

    protected static ?string $pluralModelLabel = 'Data Kepala Madrasah/Sekolah';

    protected static ?string $recordTitleAttribute = 'latest_sk_number';

    public static function form(Schema $schema): Schema
    {
        return SchoolHeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SchoolHeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolHeadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [DocumentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolHeads::route('/'),
            'create' => CreateSchoolHead::route('/create'),
            'view' => ViewSchoolHead::route('/{record}'),
            'edit' => EditSchoolHead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user instanceof User || ! self::isAllowedUser($user)) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->accessibleTo($user)
            ->with(['employee', 'school']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User && self::isAllowedUser(auth()->user());
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof SchoolHead
            && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canView($record);
    }

    private static function isAllowedUser(User $user): bool
    {
        return $user->isAdminInduk() || $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
    }
}
