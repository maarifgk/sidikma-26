<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Models\Activity;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['subject', 'causer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Modul')
                    ->badge()
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('subject_type_label')
                    ->label('Jenis Data')
                    ->getStateUsing(fn (Activity $record): string => $record->subjectTypeLabel()),
                TextColumn::make('subject_label')
                    ->label('Data')
                    ->getStateUsing(fn (Activity $record): string => $record->subjectLabel())
                    ->wrap(),
                TextColumn::make('causer_label')
                    ->label('Pelaku')
                    ->getStateUsing(fn (Activity $record): string => $record->causerLabel()),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Modul')
                    ->options([
                        'user' => 'User',
                        'authorization' => 'Role & Permission',
                        'school' => 'Sekolah/Madrasah',
                        'employee' => 'Guru/Pegawai',
                        'approval' => 'Approval',
                    ]),
                SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        'restored' => 'Dipulihkan',
                        'submitted' => 'Diajukan',
                        'verified' => 'Diverifikasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'role_attached' => 'Role Diberikan',
                        'role_detached' => 'Role Dicabut',
                        'permission_attached' => 'Permission Diberikan',
                        'permission_detached' => 'Permission Dicabut',
                    ]),
                SelectFilter::make('subject_type')
                    ->label('Jenis Data')
                    ->options(Activity::subjectTypeOptions()),
                Filter::make('created_at')
                    ->label('Rentang Waktu')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->afterOrEqual('from'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail'),
            ])
            ->emptyStateHeading('Belum ada aktivitas')
            ->emptyStateDescription('Perubahan data penting akan tercatat otomatis di halaman ini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
