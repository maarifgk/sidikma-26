<?php

namespace App\Filament\Resources\ApprovalRequests\Schemas;

use App\Models\ApprovalRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApprovalRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pengajuan')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(
                                fn (string $state): string => ApprovalRequest::statusOptions()[$state] ?? $state,
                            ),
                        TextEntry::make('approvable_type')
                            ->label('Jenis Data')
                            ->formatStateUsing(
                                fn (string $state): string => ApprovalRequest::approvableTypeOptions()[$state] ?? $state,
                            ),
                        TextEntry::make('approvable_label')
                            ->label('Data yang Diajukan')
                            ->getStateUsing(fn (ApprovalRequest $record): string => $record->approvableLabel()),
                        TextEntry::make('created_at')
                            ->label('Draft Dibuat')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2),
                Section::make('Pengajuan')
                    ->schema([
                        TextEntry::make('submittedBy.name')
                            ->label('Diajukan Oleh')
                            ->placeholder('Belum diajukan'),
                        TextEntry::make('submitted_at')
                            ->label('Waktu Pengajuan')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('submission_notes')
                            ->label('Catatan Pengajuan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Verifikasi')
                    ->schema([
                        TextEntry::make('verifiedBy.name')
                            ->label('Diverifikasi Oleh')
                            ->placeholder('Belum diverifikasi'),
                        TextEntry::make('verified_at')
                            ->label('Waktu Verifikasi')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('verification_notes')
                            ->label('Catatan Verifikasi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Keputusan')
                    ->schema([
                        TextEntry::make('decidedBy.name')
                            ->label('Diputuskan Oleh')
                            ->placeholder('Belum diputuskan'),
                        TextEntry::make('decided_at')
                            ->label('Waktu Keputusan')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('decision_notes')
                            ->label('Catatan Keputusan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
