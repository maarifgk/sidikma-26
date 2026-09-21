<?php

namespace App\Filament\Resources\SchoolHeads\Tables;

use App\Models\SchoolHead;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolHeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')->label('No')->rowIndex(),
                ImageColumn::make('employee.avatar_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(48)
                    ->defaultImageUrl(asset('images/default-avatar.svg')),
                TextColumn::make('employee.name')->label('Nama Kepala')->searchable()->sortable()->wrap(),
                TextColumn::make('identity_number')
                    ->label('NIP/NUPTK/NPK')
                    ->state(fn (SchoolHead $record): string => $record->employee?->nip
                        ?: $record->employee?->nuptk
                        ?: $record->employee?->employee_code
                        ?: '-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'employee',
                        fn (Builder $query): Builder => $query
                            ->where('nip', 'like', "%{$search}%")
                            ->orWhere('nuptk', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%"),
                    )),
                TextColumn::make('school.name')->label('Madrasah/Sekolah')->searchable()->sortable()->wrap(),
                TextColumn::make('started_at')->label('Mulai Menjabat')->date('d M Y')->placeholder('-')->sortable(),
                TextColumn::make('sk_period')
                    ->label('Masa Berlaku SK')
                    ->state(fn (SchoolHead $record): string => ($record->sk_start_date?->format('d M Y') ?? '-')
                        .' — '.($record->sk_end_date?->format('d M Y') ?? '-'))
                    ->wrap(),
                TextColumn::make('sk_validity_status')
                    ->label('Status SK')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SchoolHead::validityLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expiring' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('school_id')->label('Madrasah/Sekolah')->relationship('school', 'name')->searchable()->preload(),
                SelectFilter::make('status')->label('Status Kepala')->options([
                    SchoolHead::STATUS_ACTIVE => 'Aktif',
                    SchoolHead::STATUS_INACTIVE => 'Tidak Aktif',
                ]),
                SelectFilter::make('sk_validity')
                    ->label('Masa Berlaku SK')
                    ->options(['active' => 'Aktif', 'expiring' => 'Akan Berakhir', 'expired' => 'Berakhir'])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        $limit = today()->addDays((int) config('school-head.expiring_warning_days', 90));

                        return match ($value) {
                            'expired' => $query->whereDate('sk_end_date', '<', today()),
                            'expiring' => $query->whereBetween('sk_end_date', [today(), $limit]),
                            'active' => $query->where(fn (Builder $query): Builder => $query
                                ->whereNull('sk_end_date')
                                ->orWhereDate('sk_end_date', '>', $limit)),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Detail'),
                EditAction::make()->iconButton()->tooltip('Edit'),
                DeleteAction::make()->iconButton()->tooltip('Hapus'),
            ])
            ->emptyStateHeading('Data Kepala Madrasah/Sekolah belum tersedia')
            ->emptyStateDescription('Silakan lengkapi data Kepala Madrasah/Sekolah.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()->label('Lengkapi Data Kepala'),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->defaultSort('started_at', 'desc');
    }
}
