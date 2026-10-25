<?php

namespace App\Filament\Resources\SipinterUpdates\Tables;

use App\Models\SipinterUpdate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SipinterUpdatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('school.name')
                    ->label('Asal Madrasah')
                    ->state(fn (SipinterUpdate $record): string => $record->school?->name ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('npsn')
                    ->label('NPSN')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('school_address')
                    ->label('Alamat')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('land_ownership')
                    ->label('Kepemilikan Tanah')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('land_status')
                    ->label('Status Tanah')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('management_authority')
                    ->label('Pengelolaan')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                self::fileColumn('request_file_path', 'File Permohonan'),
                self::fileColumn('asset_file_path', 'File Aset'),
                self::fileColumn('recommendation_file_path', 'File Rekomendasi'),
                TextColumn::make('verification_status')
                    ->label('Status Registrasi')
                    ->state(fn (SipinterUpdate $record): string => collect(SipinterUpdate::FILE_FIELDS)
                        ->every(fn (string $field): bool => filled($record->getAttribute($field)) && $record->getAttribute($field) !== '-')
                        ? 'Dokumen sudah lengkap'
                        : 'Belum terverifikasi')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Dokumen sudah lengkap' ? 'success' : 'warning'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Up')
                    ->color('success'),
                DeleteAction::make()
                    ->label('Del')
                    ->color('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada pembaruan data SIPINTER')
            ->emptyStateDescription('Klik Input Data untuk menambahkan berkas pembaruan dari madrasah/sekolah.')
            ->emptyStateIcon('heroicon-o-circle-stack');
    }

    private static function fileColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make("{$name}_action")
            ->label($label)
            ->state(fn (SipinterUpdate $record): string => filled($record->getAttribute($name)) && Storage::disk(SipinterUpdate::DISK)->exists($record->getAttribute($name))
                ? 'Lihat'
                : 'Belum tersedia')
            ->badge()
            ->color(fn (string $state): string => $state === 'Lihat' ? 'primary' : 'gray')
            ->alignCenter()
            ->url(fn (SipinterUpdate $record): ?string => filled($record->getAttribute($name)) && Storage::disk(SipinterUpdate::DISK)->exists($record->getAttribute($name))
                ? Storage::disk(SipinterUpdate::DISK)
                    ->temporaryUrl($record->getAttribute($name), now()->addMinutes(5))
                : null)
            ->openUrlInNewTab();
    }
}
