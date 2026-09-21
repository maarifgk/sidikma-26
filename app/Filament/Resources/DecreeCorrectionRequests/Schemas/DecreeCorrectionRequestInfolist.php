<?php

namespace App\Filament\Resources\DecreeCorrectionRequests\Schemas;

use App\Models\DecreeCorrectionRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DecreeCorrectionRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi SK')->schema([
                TextEntry::make('request_number')->label('Nomor Pengajuan'), TextEntry::make('request_date')->label('Tanggal Pengajuan')->date('d M Y'),
                TextEntry::make('decree_number')->label('Nomor SK'), TextEntry::make('decree_date')->label('Tanggal SK')->date('d M Y'),
                TextEntry::make('subject_name')->label('Nama'), TextEntry::make('school.name')->label('Asal Sekolah/Madrasah'),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => DecreeCorrectionRequest::statusOptions()[$state] ?? $state)->color(fn ($state) => DecreeCorrectionRequest::statusColor($state)),
            ])->columns(2),
            Section::make('Perbaikan yang Diminta')->schema([
                TextEntry::make('correction_part')->label('Bagian')->formatStateUsing(fn ($state) => DecreeCorrectionRequest::correctionPartOptions()[$state] ?? $state),
                TextEntry::make('old_data')->label('DATA LAMA')->columnSpan(1), TextEntry::make('new_data')->label('DATA BARU')->columnSpan(1),
                TextEntry::make('reason')->label('Alasan/Keterangan')->columnSpanFull(), TextEntry::make('admin_notes')->label('Catatan Admin Induk')->placeholder('-')->columnSpanFull(),
            ])->columns(2),
            Section::make('Audit Proses')->schema([
                TextEntry::make('submitter.name')->label('Diajukan Oleh'), TextEntry::make('submitted_at')->dateTime('d M Y H:i')->placeholder('-'),
                TextEntry::make('processor.name')->label('Diproses Oleh')->placeholder('-'), TextEntry::make('processed_at')->dateTime('d M Y H:i')->placeholder('-'),
                TextEntry::make('approver.name')->label('Disetujui Oleh')->placeholder('-'), TextEntry::make('approved_at')->dateTime('d M Y H:i')->placeholder('-'),
            ])->columns(2),
        ]);
    }
}
