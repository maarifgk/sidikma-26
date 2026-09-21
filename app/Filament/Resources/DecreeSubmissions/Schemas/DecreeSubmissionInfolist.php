<?php

namespace App\Filament\Resources\DecreeSubmissions\Schemas;

use App\Models\DecreeSubmission;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DecreeSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Pengajuan SK')->schema([
                TextEntry::make('submission_number')->label('Nomor Pengajuan'),
                TextEntry::make('submission_date')->label('Tanggal Pengajuan')->date('d M Y'),
                TextEntry::make('school.name')->label('Sekolah/Madrasah'),
                TextEntry::make('employee.name')->label('Nama Guru'),
                TextEntry::make('type.name')->label('Jenis SK'),
                TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => DecreeSubmission::statusOptions()[$state] ?? $state),
                TextEntry::make('purpose')->label('Keterangan / Keperluan')->placeholder('-')->columnSpanFull(),
                TextEntry::make('admin_notes')->label('Catatan Admin Induk')->placeholder('-')->columnSpanFull(),
                TextEntry::make('completed_at')->label('Tanggal Selesai')->dateTime('d M Y H:i')->placeholder('-'),
                TextEntry::make('result_file_path')->label('File SK')->formatStateUsing(fn (?string $state): string => filled($state) ? 'Download File SK' : '-')->url(fn (DecreeSubmission $record): ?string => filled($record->result_file_path) && $record->status === DecreeSubmission::STATUS_COMPLETED ? route('decree-submissions.download', $record) : null)->icon('heroicon-o-arrow-down-tray'),
            ])->columns(2),
            Section::make('Riwayat Status')->schema([
                RepeatableEntry::make('statusHistories')->label('')->schema([
                    TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                    TextEntry::make('from_status')->label('Status Sebelumnya')->formatStateUsing(fn (?string $state): string => $state ? (DecreeSubmission::statusOptions()[$state] ?? $state) : '-'),
                    TextEntry::make('to_status')->label('Status Baru')->badge()->formatStateUsing(fn (string $state): string => DecreeSubmission::statusOptions()[$state] ?? $state),
                    TextEntry::make('changedBy.name')->label('Diubah Oleh')->placeholder('Sistem'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
                ])->columns(4),
            ]),
        ]);
    }
}
