<?php

namespace App\Filament\Resources\FoundationWorkPrograms;

use App\Filament\Resources\FoundationWorkPrograms\Pages\CreateFoundationWorkProgram;
use App\Filament\Resources\FoundationWorkPrograms\Pages\EditFoundationWorkProgram;
use App\Filament\Resources\FoundationWorkPrograms\Pages\ListFoundationWorkPrograms;
use App\Filament\Resources\FoundationWorkPrograms\Schemas\FoundationWorkProgramForm;
use App\Filament\Resources\FoundationWorkPrograms\Tables\FoundationWorkProgramsTable;
use App\Models\Foundation;
use App\Models\FoundationWorkProgram;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FoundationWorkProgramResource extends Resource
{
    protected static ?string $model = FoundationWorkProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Program Kerja';

    protected static ?string $pluralModelLabel = 'Program Kerja';

    public static function form(Schema $schema): Schema
    {
        return FoundationWorkProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoundationWorkProgramsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFoundationWorkPrograms::route('/'),
            'create' => CreateFoundationWorkProgram::route('/create'),
            'edit' => EditFoundationWorkProgram::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('foundation_id', Foundation::application()->getKey());
    }
}
