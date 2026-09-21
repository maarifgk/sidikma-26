<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class UsersTable
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
                    ->size(44)
                    ->defaultImageUrl(asset('images/default-avatar.svg')),
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN_INDUK => 'Admin Induk',
                        User::ROLE_ADMIN_SEKOLAH_MADRASAH => 'Admin Sekolah/Madrasah',
                        User::ROLE_GURU_PEGAWAI => 'Guru/Pegawai',
                        default => $state,
                    }),
                ToggleColumn::make('is_active')
                    ->label('Status')
                    ->disabled(fn (User $record): bool => $record->is(auth()->user())
                        || ! auth()->user()?->can('update', $record))
                    ->updateStateUsing(function (User $record, bool $state): bool {
                        $record->forceFill([
                            'is_active' => $state,
                            'updated_by' => auth()->id(),
                        ])->save();

                        return $state;
                    })
                    ->sortable(),
                TextColumn::make('memberships_count')
                    ->label('Jumlah Penugasan')
                    ->counts('memberships')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updatedBy.name')
                    ->label('Diperbarui oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak aktif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit'),
                DeleteAction::make()
                    ->label('Delete')
                    ->after(function (User $record): void {
                        if (filled($record->avatar_path)) {
                            Storage::disk('public')->delete($record->avatar_path);
                        }
                    }),
            ])
            ->toolbarActions([])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada data user')
            ->emptyStateIcon('heroicon-o-users');
    }
}
