<?php

namespace App\Filament\Resources\FoundationAnnualReports\Tables;

use App\Models\FoundationAnnualReport;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class FoundationAnnualReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('budget_year')
                    ->label('Tahun Anggaran')
                    ->searchable()
                    ->sortable(),
                self::fileColumn('work_program_report_path', 'Laporan Program Kerja'),
                self::fileColumn('financial_report_path', 'Laporan Keuangan'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->color('success'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->color('danger'),
            ])
            ->defaultSort('budget_year', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada laporan tahunan')
            ->emptyStateDescription('Klik Add untuk mengunggah laporan tahunan lembaga.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
    }

    private static function fileColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make("{$name}_action")
            ->label($label)
            ->state('View PDF')
            ->badge()
            ->color('primary')
            ->alignCenter()
            ->url(fn (FoundationAnnualReport $record): string => Storage::disk(FoundationAnnualReport::DISK)
                ->temporaryUrl($record->getAttribute($name), now()->addMinutes(5)))
            ->openUrlInNewTab();
    }
}
