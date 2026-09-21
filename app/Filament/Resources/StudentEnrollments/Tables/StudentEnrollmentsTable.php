<?php

namespace App\Filament\Resources\StudentEnrollments\Tables;

use App\Models\StudentEnrollment;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentEnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(view('filament.admin.resources.student-enrollments.table-heading'))
            ->searchPlaceholder('Cari data madrasah...')
            ->recordActionsColumnLabel('Aksi')
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
                ...collect(StudentEnrollment::GRADE_FIELDS)
                    ->map(fn (string $field): TextColumn => TextColumn::make($field)
                        ->label(strtoupper($field))
                        ->alignCenter()
                        ->sortable())
                    ->all(),
                TextColumn::make('total')
                    ->label('Total')
                    ->weight('bold')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->button()->outlined()
                    ->color('success'),
                ViewAction::make()
                    ->label('Lihat')
                    ->button()->outlined()->color('info')
                    ->authorize('view')
                    ->modalHeading('Detail Jumlah Siswa')
                    ->schema([
                        TextEntry::make('school.name')->label('Madrasah'),
                        TextEntry::make('academic_year')->label('Tahun Pelajaran'),
                        ...collect(StudentEnrollment::GRADE_FIELDS)
                            ->map(fn (string $field): TextEntry => TextEntry::make($field)->label(strtoupper($field)))
                            ->all(),
                        TextEntry::make('total')->label('Total Siswa'),
                    ]),
                DeleteAction::make()
                    ->label('Hapus')
                    ->button()->outlined()
                    ->color('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada data siswa')
            ->emptyStateDescription('Klik Tambah Data untuk memasukkan rekap siswa madrasah.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
