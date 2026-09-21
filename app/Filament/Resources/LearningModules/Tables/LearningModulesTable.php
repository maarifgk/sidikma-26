<?php

namespace App\Filament\Resources\LearningModules\Tables;

use App\Models\LearningModule;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class LearningModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Daftar Modul')
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('class_name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('module_type')
                    ->label('Jenis Modul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester')
                    ->label('Semester')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chapter')
                    ->label('BAB')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('file_action')
                    ->label('File')
                    ->state(fn (LearningModule $record): string => Storage::disk(LearningModule::DISK)->exists($record->file_path) ? 'Unduh' : 'Belum tersedia')
                    ->badge()
                    ->color(fn (LearningModule $record): string => Storage::disk(LearningModule::DISK)->exists($record->file_path) ? 'primary' : 'gray')
                    ->url(fn (LearningModule $record): ?string => Storage::disk(LearningModule::DISK)->exists($record->file_path)
                        ? Storage::disk(LearningModule::DISK)->temporaryUrl($record->file_path, now()->addMinutes(5)) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('uploaded_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Hapus')
                    ->color('danger'),
            ])
            ->defaultSort('uploaded_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada modul')
            ->emptyStateDescription('Gunakan formulir Upload Modul Baru untuk menambahkan modul.')
            ->emptyStateIcon('heroicon-o-book-open');
    }
}
