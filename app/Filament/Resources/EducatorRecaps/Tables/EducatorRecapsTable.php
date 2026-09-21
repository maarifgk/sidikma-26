<?php

namespace App\Filament\Resources\EducatorRecaps\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EducatorRecapsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(view('filament.admin.resources.educator-recaps.table-heading'))
            ->searchPlaceholder('Cari madrasah...')
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('academic_year')
                    ->label('Tahun')
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('Madrasah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('asn_certified')
                    ->label('ASN Sertifikasi')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('asn_uncertified')
                    ->label('ASN Non Sertifikasi')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('foundation_certified_inpassing')
                    ->label('Yayasan Sertifikasi/Inpassing')
                    ->alignCenter()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('foundation_uncertified')
                    ->label('Yayasan Non-Sertifikasi')
                    ->alignCenter()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->weight('bold')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                EditAction::make()
                    ->label('Edit')
                    ->color('success'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->color('danger'),
                ])->label('Aksi')->iconButton(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada data tenaga pendidik')
            ->emptyStateDescription('Klik Tambah Data untuk memasukkan jumlah tenaga pendidik madrasah.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
