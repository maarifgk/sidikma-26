<?php

namespace App\Filament\Resources\FoundationWorkPrograms\Tables;

use App\Models\FoundationWorkProgram;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FoundationWorkProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Program Kerja')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('implementation_date')
                    ->label('Tanggal Pelaksanaan')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('budget')
                    ->label('Anggaran')
                    ->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Keterangan')
                    ->formatStateUsing(
                        fn (string $state): string => FoundationWorkProgram::statusOptions()[$state] ?? $state,
                    )
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        FoundationWorkProgram::STATUS_COMPLETED => 'success',
                        FoundationWorkProgram::STATUS_PLANNED => 'warning',
                        FoundationWorkProgram::STATUS_NOT_IMPLEMENTED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->color('success'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->color('danger'),
            ])
            ->defaultSort('implementation_date', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada program kerja')
            ->emptyStateDescription('Klik Add untuk menambahkan program kerja lembaga.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
