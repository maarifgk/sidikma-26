<?php

namespace App\Filament\Resources\DecreeProposals\Schemas;

use App\Models\DecreeProposal;
use App\Models\Employee;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class DecreeProposalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Pengajuan')
                    ->schema([
                        ImageEntry::make('photo_path')
                            ->label('Foto Calon Guru/Pegawai')
                            ->state(fn (DecreeProposal $record): ?string => filled($record->photo_path)
                                && Storage::disk('decree-proposals')->exists($record->photo_path)
                                ? route('decree-proposals.documents.view', [
                                    'proposal' => $record,
                                    'document' => 'photo_path',
                                ])
                                : null)
                            ->circular()
                            ->size(96),
                        TextEntry::make('candidate_name')
                            ->label('Nama Lengkap')
                            ->state(fn (DecreeProposal $record): string => $record->displayName())
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('candidate_school')
                            ->label('Asal Madrasah/Sekolah')
                            ->state(fn (DecreeProposal $record): string => $record->displaySchoolName())
                            ->placeholder('-'),
                        TextEntry::make('id')
                            ->label('Nomor Pengajuan')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Pengajuan')
                            ->dateTime('j F Y, H:i'),
                        TextEntry::make('status')
                            ->label('Status Pengajuan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                DecreeProposal::STATUS_COMPLETED => 'success',
                                DecreeProposal::STATUS_REJECTED => 'danger',
                                default => 'warning',
                            })
                            ->formatStateUsing(
                                fn (string $state): string => DecreeProposal::statusOptions()[$state] ?? $state,
                            ),
                    ])
                    ->columns(3),
                Section::make('Identitas Pribadi dan Pengajuan')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Nomor Pengajuan')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Pengajuan')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('submittedBy.name')
                            ->label('Diajukan oleh')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(
                                fn (string $state): string => DecreeProposal::statusOptions()[$state] ?? $state,
                            ),
                        TextEntry::make('candidate_school')
                            ->label('Asal Madrasah/Sekolah')
                            ->state(fn (DecreeProposal $record): string => $record->displaySchoolName())
                            ->placeholder('-'),
                        TextEntry::make('candidate_name')
                            ->label('Nama Lengkap')
                            ->state(fn (DecreeProposal $record): string => $record->displayName())
                            ->placeholder('-'),
                        TextEntry::make('candidate_email')
                            ->label('Email')
                            ->state(fn (DecreeProposal $record): string => $record->displayEmail())
                            ->placeholder('-'),
                        TextEntry::make('candidate_phone')
                            ->label('Nomor Telepon')
                            ->state(fn (DecreeProposal $record): string => $record->displayPhone())
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.employee_code')
                            ->label('EWANUGK')
                            ->state(fn (DecreeProposal $record): string => $record->displayEmployeeCode())
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.nuptk')
                            ->label('NUPTK/NPK')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->nuptk
                                ?: $record->candidateSnapshotValue('nuptk', '-')))
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.birth_place')
                            ->label('Tempat Lahir')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->birth_place
                                ?: $record->candidateSnapshotValue('birth_place', '-')))
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.birth_date')
                            ->label('Tanggal Lahir')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->birth_date?->format('d M Y')
                                ?: $record->candidateSnapshotValue('birth_date', '-')))
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.employment_status')
                            ->label('Status Kepegawaian')
                            ->state(fn (DecreeProposal $record): ?string => $record->employee?->employment_status
                                ?: $record->candidateSnapshotValue('employment_status'))
                            ->formatStateUsing(fn (?string $state): string => Employee::employmentStatusLabel($state))
                            ->placeholder('-'),
                        TextEntry::make('employee.nip')
                            ->label('NIP')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->nip
                                ?: $record->candidateSnapshotValue('nip', '-')))
                            ->placeholder('-')
                            ->visible(fn (DecreeProposal $record): bool => Employee::isPnsStatus(
                                $record->employee?->employment_status ?: $record->candidateSnapshotValue('employment_status'),
                            )),
                        TextEntry::make('employee.rank')
                            ->label('Pangkat')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->rank
                                ?: $record->candidateSnapshotValue('rank', '-')))
                            ->placeholder('-')
                            ->visible(fn (DecreeProposal $record): bool => Employee::isPnsStatus(
                                $record->employee?->employment_status ?: $record->candidateSnapshotValue('employment_status'),
                            )),
                        TextEntry::make('employee.grade')
                            ->label('Golongan')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->grade
                                ?: $record->candidateSnapshotValue('grade', '-')))
                            ->placeholder('-')
                            ->visible(fn (DecreeProposal $record): bool => Employee::isPnsStatus(
                                $record->employee?->employment_status ?: $record->candidateSnapshotValue('employment_status'),
                            )),
                        TextEntry::make('candidate_snapshot.last_education')
                            ->label('Pendidikan Terakhir')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->last_education
                                ?: $record->candidateSnapshotValue('last_education', '-')))
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.program_study')
                            ->label('Program Studi')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->program_study
                                ?: $record->candidateSnapshotValue('program_study', '-')))
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.position_name')
                            ->label('Ketugasan')
                            ->state(fn (DecreeProposal $record): string => $record->displayPositionName())
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.assignment_start_date')
                            ->label('TMT Pertama')
                            ->state(fn (DecreeProposal $record): string => (string) ($record->employee?->currentAssignment?->start_date?->format('d M Y')
                                ?: $record->candidateSnapshotValue('assignment_start_date', '-')))
                            ->placeholder('-'),
                        TextEntry::make('partner_admin_number')
                            ->label('Nomor Mitra Admin')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Catatan Pengajuan')
                            ->state(fn (DecreeProposal $record): ?string => $record->initialNotes())
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('rejection_reason')
                            ->label('KETERANGAN PENOLAKAN')
                            ->state(fn (DecreeProposal $record): string => $record->rejectionReason() ?? 'Keterangan penolakan belum tersedia.')
                            ->visible(fn (DecreeProposal $record): bool => $record->status === DecreeProposal::STATUS_REJECTED)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Berkas Persyaratan')
                    ->schema([
                        self::downloadEntry('photo_path', 'Foto Resmi', true),
                        self::downloadEntry('diploma_path', 'Ijazah Terakhir', true),
                        self::downloadEntry('application_letter_path', 'Surat Permohonan', true),
                        self::downloadEntry('service_statement_path', 'Surat Pernyataan Siap Berkhidmat', true),
                        self::downloadEntry('mwc_recommendation_request_path', 'Rekomendasi MWC', false),
                        self::downloadEntry('teaching_certificate_path', 'Akta Mengajar', false),
                        self::downloadEntry('educator_certificate_path', 'Sertifikat Pendidik', false),
                        self::downloadEntry(
                            'task_assignment_certificate_path',
                            'Sertifikat Pembagian Tugas dari Kepala Sekolah/Madrasah',
                            true,
                        ),
                    ])
                    ->columns(2),
            ]);
    }

    private static function downloadEntry(string $name, string $label, bool $required): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->formatStateUsing(function (?string $state) use ($required): string {
                if (blank($state)) {
                    return $required ? 'Belum tersedia' : 'Tidak diunggah (opsional)';
                }

                return Storage::disk('decree-proposals')->exists($state)
                    ? 'Lihat / unduh '.basename($state)
                    : 'Path tercatat, file tidak ditemukan';
            })
            ->icon('heroicon-o-arrow-down-tray')
            ->color(fn (?string $state): string => blank($state)
                ? ($required ? 'danger' : 'gray')
                : (Storage::disk('decree-proposals')->exists($state) ? 'success' : 'warning'))
            ->url(fn (DecreeProposal $record, ?string $state): ?string => filled($state)
                && Storage::disk('decree-proposals')->exists($state)
                ? route('decree-proposals.documents.view', [
                    'proposal' => $record,
                    'document' => $name,
                ])
                : null)
            ->openUrlInNewTab();
    }
}
