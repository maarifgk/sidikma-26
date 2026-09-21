<?php

namespace App\Filament\Resources\SchoolProfiles\RelationManagers;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolProfileEmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    protected static ?string $title = 'Data Guru & Pegawai';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('is_active', true)
                ->with(['currentAssignment.position']))
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                ImageColumn::make('avatar_path')
                    ->label('Image')
                    ->disk('public')
                    ->circular()
                    ->size(48)
                    ->defaultImageUrl(asset('images/default-avatar.svg')),
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('employment_status')
                    ->label('Status Kepegawaian')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => Employee::employmentStatusLabel($state))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('currentAssignment.position.name')
                    ->label('Ketugasan')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('viewEmployee')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Lihat detail guru/pegawai')
                    ->url(fn (Employee $record): string => EmployeeResource::getUrl(
                        'view',
                        ['record' => $record],
                        panel: 'admin',
                        isAbsolute: false,
                    )),
            ])
            ->recordActionsColumnLabel('Actions')
            ->filters([])
            ->toolbarActions([])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada data guru/pegawai')
            ->emptyStateDescription('Guru atau pegawai aktif dari Master Data akan tampil di sini.')
            ->emptyStateIcon('heroicon-o-identification');
    }
}
