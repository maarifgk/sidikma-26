<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Models\Activity;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aktivitas')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Waktu')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('log_name')
                            ->label('Modul')
                            ->badge(),
                        TextEntry::make('event')
                            ->label('Event')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->label('Deskripsi'),
                        TextEntry::make('subject_type_label')
                            ->label('Jenis Data')
                            ->getStateUsing(fn (Activity $record): string => $record->subjectTypeLabel()),
                        TextEntry::make('subject_label')
                            ->label('Data')
                            ->getStateUsing(fn (Activity $record): string => $record->subjectLabel()),
                        TextEntry::make('causer_label')
                            ->label('Pelaku')
                            ->getStateUsing(fn (Activity $record): string => $record->causerLabel()),
                        TextEntry::make('batch_uuid')
                            ->label('Batch UUID')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Perubahan Data')
                    ->schema([
                        KeyValueEntry::make('old_values')
                            ->label('Nilai Lama')
                            ->getStateUsing(fn (Activity $record): array => $record->oldValues()),
                        KeyValueEntry::make('new_values')
                            ->label('Nilai Baru')
                            ->getStateUsing(fn (Activity $record): array => $record->newValues()),
                    ])
                    ->columns(2),
                Section::make('Konteks Permintaan')
                    ->schema([
                        TextEntry::make('ip_address')
                            ->label('Alamat IP')
                            ->getStateUsing(
                                fn (Activity $record): string => $record->properties?->get('ip_address') ?? 'Tidak tersedia',
                            ),
                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->getStateUsing(
                                fn (Activity $record): string => $record->properties?->get('user_agent') ?? 'Tidak tersedia',
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
