<?php

namespace App\Filament\Resources\SchoolProfiles\Tables;

use App\Filament\Resources\SchoolProfiles\SchoolProfileResource;
use App\Models\School;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchoolProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Sekolah/Madrasah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('profile_action')
                    ->label('Action')
                    ->state('View Profile')
                    ->icon('heroicon-o-eye')
                    ->badge()
                    ->color('primary')
                    ->url(fn (School $record): string => SchoolProfileResource::getUrl(
                        'view',
                        ['record' => $record],
                        panel: 'admin',
                        isAbsolute: false,
                    )),
            ])
            ->defaultSort('name')
            ->recordActions([])
            ->filters([])
            ->toolbarActions([])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada profil madrasah/sekolah')
            ->emptyStateDescription('Data madrasah atau sekolah belum tersedia.')
            ->emptyStateActions([])
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}
