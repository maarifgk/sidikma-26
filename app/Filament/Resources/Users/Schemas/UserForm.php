<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('NAMA USERS')
                    ->placeholder('Pilih Sekolah/Madrasah')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('EMAIL ADMIN MADRASAH/SEKOLAH')
                    ->placeholder('Masukan Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('PASSWORD')
                    ->placeholder('Masukan Password')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('role_name')
                    ->label('ROLE')
                    ->placeholder('-- Pilih --')
                    ->options([
                        User::ROLE_ADMIN_INDUK => 'Admin Induk',
                        User::ROLE_ADMIN_SEKOLAH_MADRASAH => 'Admin Sekolah/Madrasah',
                        User::ROLE_GURU_PEGAWAI => 'Guru/Pegawai',
                    ])
                    ->required()
                    ->native(false)
                    ->visibleOn('create'),
                Select::make('roles')
                    ->label('Role')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->whereIn('name', [
                                User::ROLE_ADMIN_INDUK,
                                User::ROLE_ADMIN_SEKOLAH_MADRASAH,
                                User::ROLE_GURU_PEGAWAI,
                            ])
                            ->orderBy('name'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Role $record): string => self::roleLabel($record->name),
                    )
                    ->multiple()
                    ->maxItems(1)
                    ->required()
                    ->preload()
                    ->searchable()
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->visibleOn('edit'),
            ])
            ->columns(2);
    }

    private static function roleLabel(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN_INDUK => 'Admin Induk',
            User::ROLE_ADMIN_SEKOLAH_MADRASAH => 'Admin Sekolah/Madrasah',
            User::ROLE_GURU_PEGAWAI => 'Guru/Pegawai',
            default => $role,
        };
    }
}
