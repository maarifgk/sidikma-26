<?php

namespace App\Filament\Resources\EmployeeMutations\Tables;

use App\Models\EmployeeMutation;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EmployeeMutationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('employee_code')
                    ->label('EWANUGK')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('mutation_type')
                    ->label('Jenis Mutasi')
                    ->formatStateUsing(
                        fn (string $state): string => EmployeeMutation::typeOptions()[$state] ?? $state,
                    )
                    ->sortable(),
                TextColumn::make('origin_school_name')
                    ->label('Sekolah/Madrasah Asal')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('destination_school_name')
                    ->label('Sekolah/Madrasah Tujuan')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('request_letter_action')
                    ->label('Surat Permohonan')
                    ->state(fn (EmployeeMutation $record): string => Storage::disk(EmployeeMutation::DISK)->exists($record->request_letter_path) ? 'Lihat' : 'Belum tersedia')
                    ->badge()
                    ->color(fn (EmployeeMutation $record): string => Storage::disk(EmployeeMutation::DISK)->exists($record->request_letter_path) ? 'primary' : 'gray')
                    ->alignCenter()
                    ->url(fn (EmployeeMutation $record): ?string => Storage::disk(EmployeeMutation::DISK)->exists($record->request_letter_path)
                        ? Storage::disk(EmployeeMutation::DISK)->temporaryUrl($record->request_letter_path, now()->addMinutes(5)) : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                SelectFilter::make('mutation_type')
                    ->label('Jenis Mutasi')
                    ->options(EmployeeMutation::typeOptions()),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Delete')
                    ->color('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada permohonan mutasi')
            ->emptyStateDescription('Klik Ajukan untuk menambahkan permohonan mutasi guru atau pegawai.')
            ->emptyStateIcon('heroicon-o-arrows-right-left');
    }
}
