<?php

namespace App\Filament\Resources\DecreeProposals\Schemas;

use App\Models\DecreeProposal;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class DecreeProposalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Pengajuan')
                    ->icon('heroicon-o-identification')
                    ->iconColor('success')
                    ->extraAttributes(['class' => 'decree-proposal-detail-section decree-proposal-hero-section'])
                    ->schema([
                        ImageEntry::make('photo_path')
                            ->label('Foto Calon Guru/Pegawai')
                            ->state(fn (DecreeProposal $record): ?string => filled($record->getAttribute('photo_path'))
                                && Storage::disk('decree-proposals')->exists($record->getAttribute('photo_path'))
                                ? route('decree-proposals.documents.view', [
                                    'proposal' => $record,
                                    'document' => 'photo_path',
                                ])
                                : null)
                            ->imageWidth(180)
                            ->imageHeight(240)
                            ->extraAttributes(['class' => 'decree-proposal-photo-entry'])
                            ->extraImgAttributes([
                                'class' => 'decree-proposal-photo',
                                'style' => 'object-fit: contain; background: #eef2ef;',
                            ]),
                        TextEntry::make('candidate_name')
                            ->label('Nama Lengkap')
                            ->state(fn (DecreeProposal $record): string => $record->displayName())
                            ->extraAttributes(['class' => 'decree-proposal-field-value']),
                        TextEntry::make('candidate_school')
                            ->label('Asal Madrasah/Sekolah')
                            ->state(fn (DecreeProposal $record): string => $record->displaySchoolName())
                            ->placeholder('-'),
                        TextEntry::make('id')
                            ->label('Nomor Pengajuan')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Pengajuan')
                            ->state(fn (DecreeProposal $record): string => self::formatDateTime($record->getAttribute('created_at'))),
                        TextEntry::make('status')
                            ->label('Status Pengajuan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                DecreeProposal::STATUS_APPROVED,
                                DecreeProposal::STATUS_COMPLETED => 'success',
                                DecreeProposal::STATUS_REVISION => 'info',
                                DecreeProposal::STATUS_REJECTED => 'danger',
                                default => 'warning',
                            })
                            ->formatStateUsing(
                                fn (string $state): string => DecreeProposal::statusOptions()[$state] ?? $state,
                            ),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Grid::make(2)
                    ->extraAttributes(['class' => 'decree-proposal-main-layout'])
                    ->schema([
                        Group::make([
                Section::make('Identitas Pribadi dan Pengajuan')
                    ->icon('heroicon-o-user')
                    ->iconColor('success')
                    ->extraAttributes(['class' => 'decree-proposal-detail-section decree-proposal-identity-section'])
                    ->schema([
                        TextEntry::make('submittedBy.name')
                            ->label('Diajukan oleh')
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
                            ->state(fn (DecreeProposal $record): string => self::formatDate(
                                $record->employee?->birth_date ?: $record->candidateSnapshotValue('birth_date'),
                            )),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make('Data Pendidikan')
                    ->icon('heroicon-o-academic-cap')
                    ->iconColor('info')
                    ->extraAttributes(['class' => 'decree-proposal-detail-section decree-proposal-education-section'])
                    ->schema([
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
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make('Data Kepegawaian')
                    ->icon('heroicon-o-briefcase')
                    ->iconColor('success')
                    ->extraAttributes(['class' => 'decree-proposal-detail-section decree-proposal-employment-section'])
                    ->schema([
                        TextEntry::make('candidate_snapshot.employment_status')
                            ->label('Status Kepegawaian')
                            ->state(fn (DecreeProposal $record): ?string => $record->employee?->employment_status
                                ?: $record->candidateSnapshotValue('employment_status'))
                            ->formatStateUsing(fn (?string $state): string => Employee::employmentStatusLabel($state))
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.position_name')
                            ->label('Ketugasan')
                            ->state(fn (DecreeProposal $record): string => $record->displayPositionName())
                            ->placeholder('-'),
                        TextEntry::make('candidate_snapshot.assignment_start_date')
                            ->label('TMT Pertama')
                            ->state(fn (DecreeProposal $record): string => self::formatDate(
                                $record->employee?->currentAssignment?->start_date
                                    ?: $record->candidateSnapshotValue('assignment_start_date'),
                            )),
                        TextEntry::make('partner_admin_number')
                            ->label('Nomor Mitra Admin')
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
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                        ])
                            ->extraAttributes(['class' => 'decree-proposal-main-column decree-proposal-main-column--left'])
                            ->columnSpan(1),
                        Group::make([
                Section::make('Berkas Persyaratan')
                    ->icon('heroicon-o-folder-open')
                    ->iconColor('info')
                    ->extraAttributes(['class' => 'decree-proposal-detail-section decree-proposal-detail-section--documents'])
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
                    ->columns(1)
                    ->columnSpanFull(),
                        ])
                            ->extraAttributes(['class' => 'decree-proposal-main-column decree-proposal-main-column--right'])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
                Section::make('Catatan dan Verifikasi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->iconColor('warning')
                    ->extraAttributes(['class' => 'decree-proposal-detail-section decree-proposal-notes-section'])
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan Pengajuan')
                            ->state(fn (DecreeProposal $record): ?string => $record->initialNotes())
                            ->placeholder('-'),
                        TextEntry::make('rejection_reason')
                            ->label('Keterangan Pengajuan')
                            ->state(fn (DecreeProposal $record): string => $record->decisionReason()
                                ?? 'Keterangan belum tersedia.')
                            ->visible(fn (DecreeProposal $record): bool => filled($record->decisionReason())),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    private static function downloadEntry(string $name, string $label, bool $required): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->state(fn (DecreeProposal $record): ?string => $record->getAttribute($name))
            ->formatStateUsing(function (?string $state) use ($required): string {
                if (blank($state)) {
                    return $required ? 'Belum diunggah' : 'Belum diunggah (opsional)';
                }

                return Storage::disk('decree-proposals')->exists($state)
                    ? 'Dokumen tersedia'
                    : 'Belum tersedia';
            })
            ->icon('heroicon-o-document-text')
            ->color(fn (?string $state): string => blank($state)
                ? ($required ? 'danger' : 'gray')
                : (Storage::disk('decree-proposals')->exists($state) ? 'success' : 'warning'))
            ->suffixActions([
                fn (TextEntry $entry, DecreeProposal $record): array => self::documentActions(
                    $entry->getState(),
                    $record,
                    $name,
                ),
            ]);
    }

    /** @return array<Action> */
    private static function documentActions(?string $path, DecreeProposal $record, string $document): array
    {
        if (blank($path) || ! Storage::disk('decree-proposals')->exists($path)) {
            return [];
        }

        $route = fn (bool $download = false): string => route('decree-proposals.documents.view', [
            'proposal' => $record,
            'document' => $document,
            ...($download ? ['download' => 1] : []),
        ]);

        return [
            Action::make('viewDocument')
                ->label('Lihat Dokumen')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->button()
                ->url(fn (): string => $route())
                ->openUrlInNewTab(),
            Action::make('downloadDocument')
                ->label('Unduh Dokumen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button()
                ->url(fn (): string => $route(true))
                ->openUrlInNewTab(),
        ];
    }

    private static function formatDateTime(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('j F Y, H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private static function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('j F Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
