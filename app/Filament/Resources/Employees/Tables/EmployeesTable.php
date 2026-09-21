<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->wrap()
                    ->sortable(),
                TextColumn::make('employee_code')
                    ->label('EWANUGK')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('Asal Madrasah')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('employment_status')
                    ->label('Status Kepegawaian')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => Employee::employmentStatusLabel($state))
                    ->wrap()
                    ->sortable(),
                TextColumn::make('currentAssignment.position.name')
                    ->label('Ketugasan')
                    ->placeholder('-')
                    ->description(function (Employee $record): ?string {
                        $assignment = $record->currentAssignment;

                        if (! $assignment) {
                            return null;
                        }

                        $details = [];

                        if (filled($assignment->decree_number)) {
                            $details[] = "SK {$assignment->decree_number}";
                        }

                        if (filled($assignment->decree_period)) {
                            $details[] = "Periode {$assignment->decree_period}";
                        }

                        if ($assignment->start_date) {
                            $period = $assignment->start_date->format('d M Y');

                            if ($assignment->end_date) {
                                $period .= ' - '.$assignment->end_date->format('d M Y');
                            }

                            $details[] = $period;
                        }

                        return filled($details) ? implode(' • ', $details) : null;
                    })
                    ->description(null)
                    ->wrap(),
                TextColumn::make('employee_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Employee::TYPE_GURU => 'Guru',
                        Employee::TYPE_PEGAWAI => 'Pegawai',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignments_count')
                    ->label('Riwayat Penugasan')
                    ->counts('assignments')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nuptk')
                    ->label('NUPTK')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')
                    ->label('Akun User')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Sekolah/Madrasah')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('employee_type')
                    ->label('Jenis')
                    ->options([
                        Employee::TYPE_GURU => 'Guru',
                        Employee::TYPE_PEGAWAI => 'Pegawai',
                    ]),
                SelectFilter::make('employment_status')
                    ->label('Status Kepegawaian')
                    ->options(Employee::employmentStatusOptions()),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->iconButton()
                    ->tooltip('Edit'),
                ViewAction::make()
                    ->label('View')
                    ->iconButton()
                    ->tooltip('Lihat detail'),
                DeleteAction::make()
                    ->label('Delete')
                    ->iconButton()
                    ->tooltip('Hapus'),
                RestoreAction::make()
                    ->iconButton()
                    ->tooltip('Pulihkan'),
                ForceDeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus permanen')
                    ->after(function (Employee $record): void {
                        if (filled($record->avatar_path)) {
                            Storage::disk('public')->delete($record->avatar_path);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada data guru/pegawai')
            ->emptyStateDescription('Data guru atau pegawai yang ditambahkan akan tampil di halaman ini.')
            ->emptyStateIcon('heroicon-o-identification');
    }
}
