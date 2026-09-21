<?php

namespace App\Filament\Resources\LearningModules;

use App\Filament\Resources\LearningModules\Pages\ListLearningModules;
use App\Filament\Resources\LearningModules\Tables\LearningModulesTable;
use App\Models\Foundation;
use App\Models\LearningModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LearningModuleResource extends Resource
{
    protected static ?string $model = LearningModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Modul';

    protected static ?string $pluralModelLabel = 'Modul';

    public static function table(Table $table): Table
    {
        return LearningModulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningModules::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('foundation_id', Foundation::application()->getKey());
    }
}
