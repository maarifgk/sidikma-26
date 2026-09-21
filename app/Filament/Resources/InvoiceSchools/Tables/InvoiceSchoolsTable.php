<?php

namespace App\Filament\Resources\InvoiceSchools\Tables;

use App\Filament\Resources\InvoiceSchools\InvoiceSchoolResource;
use App\Filament\Resources\InvoiceSchools\Pages\ListInvoiceSchools;
use App\Models\School;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoiceSchoolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Data Madrasah/Sekolah')
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Sekolah/Madrasah')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school_level')
                    ->label('Jenjang')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? "Tingkat {$state}" : '-'),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->outlined()
                    ->url(fn (School $record, ListInvoiceSchools $livewire): string => InvoiceSchoolResource::getUrl(
                        'view',
                        [
                            'record' => $record,
                            'year' => $livewire->academicYear,
                            'mode' => 'detail',
                        ],
                        panel: 'admin',
                        isAbsolute: false,
                    )),
                Action::make('class_invoices')
                    ->label('Pembayaran Kelas')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->outlined()
                    ->url(fn (School $record, ListInvoiceSchools $livewire): string => InvoiceSchoolResource::getUrl(
                        'view',
                        [
                            'record' => $record,
                            'year' => $livewire->academicYear,
                            'mode' => 'class',
                        ],
                        panel: 'admin',
                        isAbsolute: false,
                    )),
            ])
            ->recordActionsColumnLabel('Aksi')
            ->defaultSort('name')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada data madrasah/sekolah')
            ->emptyStateDescription('Madrasah atau sekolah aktif akan tampil pada halaman ini.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}
