<?php

namespace App\Filament\Resources\SchoolHeads\Schemas;

use App\Models\Employee;
use App\Models\SchoolHead;
use App\Models\SchoolHeadAchievement;
use App\Models\SchoolHeadTraining;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolHeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profil Kepala Madrasah/Sekolah')
                ->description('Informasi profil, jabatan, dan dokumen Kepala Madrasah/Sekolah')
                ->schema([
                    ImageEntry::make('employee.avatar_path')
                        ->label('Foto')
                        ->disk('public')
                        ->defaultImageUrl(asset('images/default-avatar.svg'))
                        ->imageWidth(150)
                        ->imageHeight(200)
                        ->extraImgAttributes(['class' => 'rounded-xl object-cover', 'style' => 'aspect-ratio: 3 / 4;']),
                    TextEntry::make('employee.name')->label('Nama Lengkap')->weight('bold')->size('lg'),
                    TextEntry::make('school.name')->label('Madrasah/Sekolah'),
                    TextEntry::make('status')->label('Status Kepala')->badge()->formatStateUsing(fn (string $state): string => $state === SchoolHead::STATUS_ACTIVE ? 'AKTIF' : 'TIDAK AKTIF')->color(fn (string $state): string => $state === SchoolHead::STATUS_ACTIVE ? 'success' : 'gray'),
                    TextEntry::make('identity_number')->label('NIP/NUPTK/NPK')->state(fn (SchoolHead $record): string => $record->employee?->nip ?: $record->employee?->nuptk ?: $record->employee?->employee_code ?: '-'),
                    TextEntry::make('employee.birth_place')->label('Tempat Lahir')->placeholder('-'),
                    TextEntry::make('employee.birth_date')->label('Tanggal Lahir')->date('d M Y')->placeholder('-'),
                    TextEntry::make('employee.gender')->label('Jenis Kelamin')->formatStateUsing(fn (?string $state): string => match ($state) { Employee::GENDER_MALE => 'Laki-laki', Employee::GENDER_FEMALE => 'Perempuan', default => '-' }),
                    TextEntry::make('employee.rank')->label('Pangkat')->placeholder('-'),
                    TextEntry::make('employee.grade')->label('Golongan')->placeholder('-'),
                    TextEntry::make('employee.last_education')->label('Pendidikan Terakhir')->placeholder('-'),
                    TextEntry::make('employee.program_study')->label('Jurusan/Program Studi')->placeholder('-'),
                    TextEntry::make('employee.employment_status')->label('Status Kepegawaian')->formatStateUsing(fn (?string $state): string => Employee::employmentStatusLabel($state)),
                    TextEntry::make('employee.phone')->label('Nomor HP Pribadi')->placeholder('-'),
                    TextEntry::make('employee.email')->label('Email Pribadi')->placeholder('-'),
                ])
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3]),
            Section::make('Informasi Kepala Madrasah/Sekolah')
                ->schema([
                    TextEntry::make('position')->label('Jabatan'),
                    TextEntry::make('started_at')->label('Mulai Menjabat')->date('d M Y')->placeholder('-'),
                    TextEntry::make('latest_sk_number')->label('Nomor SK Terakhir')->placeholder('-'),
                    TextEntry::make('sk_period')->label('Masa Berlaku SK')->state(fn (SchoolHead $record): string => ($record->sk_start_date?->format('d M Y') ?? '-').' — '.($record->sk_end_date?->format('d M Y') ?? '-')),
                    TextEntry::make('sk_validity_status')->label('Status Masa Berlaku')->badge()->formatStateUsing(fn (string $state): string => SchoolHead::validityLabels()[$state] ?? $state)->color(fn (string $state): string => match ($state) { 'active' => 'success', 'expiring' => 'warning', 'expired' => 'danger', default => 'gray' }),
                ])
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3]),
            Section::make('Riwayat Jabatan Sebelumnya')
                ->schema([
                    RepeatableEntry::make('jobHistories')->label('')->schema([
                        TextEntry::make('position')->label('Jabatan')->weight('bold'),
                        TextEntry::make('institution_name')->label('Instansi'),
                        TextEntry::make('started_at')->label('Mulai')->date('d M Y'),
                        TextEntry::make('ended_at')->label('Selesai')->date('d M Y')->placeholder('Sekarang'),
                        TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                    ])->columns(2),
                ]),
            Section::make('Sertifikat Kompetensi')
                ->schema([
                    RepeatableEntry::make('certificates')->label('')->schema([
                        TextEntry::make('name')->label('Nama Sertifikat')->weight('bold'),
                        TextEntry::make('certificate_number')->label('Nomor')->placeholder('-'),
                        TextEntry::make('issuer')->label('Penerbit')->placeholder('-'),
                        TextEntry::make('issued_at')->label('Tanggal')->date('d M Y')->placeholder('-'),
                    ])->columns(2),
                ]),
            Section::make('Diklat/Pelatihan')
                ->schema([
                    RepeatableEntry::make('trainings')->label('')->schema([
                        TextEntry::make('name')->label('Nama Kegiatan')->weight('bold'),
                        TextEntry::make('activity_type')->label('Jenis')->badge()->formatStateUsing(fn (string $state): string => SchoolHeadTraining::TYPES[$state] ?? $state),
                        TextEntry::make('organizer')->label('Penyelenggara')->placeholder('-'),
                        TextEntry::make('started_at')->label('Mulai')->date('d M Y')->placeholder('-'),
                        TextEntry::make('ended_at')->label('Selesai')->date('d M Y')->placeholder('-'),
                    ])->columns(2),
                ]),
            Section::make('Prestasi')
                ->schema([
                    RepeatableEntry::make('achievements')->label('')->schema([
                        TextEntry::make('name')->label('Prestasi/Penghargaan')->weight('bold'),
                        TextEntry::make('level')->label('Tingkat')->badge()->formatStateUsing(fn (string $state): string => SchoolHeadAchievement::LEVELS[$state] ?? $state),
                        TextEntry::make('achieved_at')->label('Tanggal')->date('d M Y')->placeholder('-'),
                        TextEntry::make('organizer')->label('Penyelenggara')->placeholder('-'),
                    ])->columns(2),
                ]),
        ]);
    }
}
