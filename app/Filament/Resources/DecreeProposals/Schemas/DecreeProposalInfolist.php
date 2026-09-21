<?php

namespace App\Filament\Resources\DecreeProposals\Schemas;

use App\Models\DecreeProposal;
use App\Models\Employee;
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
                Section::make('Detail Usulan SK')
                    ->schema([
                        TextEntry::make('employee.employee_code')
                            ->label('EWANUGK')
                            ->placeholder('-'),
                        TextEntry::make('employee.name')
                            ->label('Nama Lengkap'),
                        TextEntry::make('employee.school.name')
                            ->label('Asal Madrasah/Sekolah')
                            ->placeholder('-'),
                        TextEntry::make('employee.phone')
                            ->label('Nomor Telepon')
                            ->placeholder('-'),
                        TextEntry::make('employee.employment_status')
                            ->label('Status Kepegawaian')
                            ->formatStateUsing(fn (?string $state): string => Employee::employmentStatusLabel($state))
                            ->placeholder('-'),
                        TextEntry::make('employee.nip')
                            ->label('NIP')
                            ->placeholder('-')
                            ->visible(fn (DecreeProposal $record): bool => Employee::isPnsStatus($record->employee?->employment_status)),
                        TextEntry::make('employee.rank')
                            ->label('Pangkat')
                            ->placeholder('-')
                            ->visible(fn (DecreeProposal $record): bool => Employee::isPnsStatus($record->employee?->employment_status)),
                        TextEntry::make('employee.grade')
                            ->label('Golongan')
                            ->placeholder('-')
                            ->visible(fn (DecreeProposal $record): bool => Employee::isPnsStatus($record->employee?->employment_status)),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(
                                fn (string $state): string => DecreeProposal::statusOptions()[$state] ?? $state,
                            ),
                        TextEntry::make('submittedBy.name')
                            ->label('Diajukan oleh')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Pengajuan')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Berkas Persyaratan')
                    ->schema([
                        self::downloadEntry('photo_path', 'Foto Resmi'),
                        self::downloadEntry('diploma_path', 'Ijazah Terakhir'),
                        self::downloadEntry('application_letter_path', 'Surat Permohonan'),
                        self::downloadEntry('service_statement_path', 'Surat Pernyataan Siap Berhidmad'),
                        self::downloadEntry('teaching_certificate_path', 'Akta Mengajar'),
                        self::downloadEntry('educator_certificate_path', 'Sertifikat Pendidik'),
                        self::downloadEntry(
                            'task_assignment_certificate_path',
                            'Sertifikat Pembagian Tugas dari Kepala Sekolah/Madrasah',
                        ),
                    ])
                    ->columns(2),
            ]);
    }

    private static function downloadEntry(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->formatStateUsing(fn (?string $state): string => filled($state)
                ? 'Unduh '.basename($state)
                : '-')
            ->icon('heroicon-o-arrow-down-tray')
            ->url(fn (?string $state): ?string => filled($state)
                ? Storage::disk('decree-proposals')->temporaryUrl($state, now()->addMinutes(5))
                : null)
            ->openUrlInNewTab();
    }
}
