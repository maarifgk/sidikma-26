<?php

namespace App\Filament\Resources\SchoolHeads\Schemas;

use App\Models\Document;
use App\Models\Employee;
use App\Models\School;
use App\Models\SchoolHead;
use App\Models\SchoolHeadAchievement;
use App\Models\SchoolHeadTraining;
use App\Services\DocumentStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

class SchoolHeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->extraAttributes(['class' => 'school-head-form'])
            ->components([
                Section::make('Profil Kepala Madrasah/Sekolah')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->iconColor('primary')
                    ->extraAttributes(['class' => 'school-head-section school-head-section--profile'])
                    ->description('Pilih data guru/pegawai yang sudah tersedia agar identitas tidak tersimpan ganda.')
                    ->schema([
                        Select::make('school_id')
                            ->label('Madrasah/Sekolah')
                            ->options(fn (): array => School::activeOptionsFor())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->native(false),
                        Select::make('employee_id')
                            ->label('Nama Kepala')
                            ->options(fn (Get $get): array => Employee::query()
                                ->where('school_id', $get('school_id'))
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get(['id', 'name'])
                                ->mapWithKeys(fn (Employee $employee): array => [
                                    $employee->getKey() => $employee->name,
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
                Section::make('Informasi Kepala Madrasah/Sekolah')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->iconColor('primary')
                    ->extraAttributes(['class' => 'school-head-section school-head-section--information'])
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                SchoolHead::STATUS_ACTIVE => 'Aktif',
                                SchoolHead::STATUS_INACTIVE => 'Tidak Aktif',
                            ])
                            ->default(SchoolHead::STATUS_ACTIVE)
                            ->required()
                            ->native(false),
                        TextInput::make('position')
                            ->label('Jabatan')
                            ->default('Kepala Madrasah/Sekolah')
                            ->required()
                            ->maxLength(100),
                        DatePicker::make('started_at')
                            ->label('Mulai Menjabat Sebagai Kepala')
                            ->native(false),
                        TextInput::make('latest_sk_number')
                            ->label('Nomor SK Terakhir')
                            ->maxLength(180),
                        DatePicker::make('sk_start_date')
                            ->label('Tanggal Mulai SK')
                            ->native(false),
                        DatePicker::make('sk_end_date')
                            ->label('Tanggal Berakhir SK')
                            ->afterOrEqual('sk_start_date')
                            ->native(false),
                    ])
                    ->columns(2),
                Section::make('Riwayat Jabatan Sebelumnya')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->iconColor('primary')
                    ->extraAttributes(['class' => 'school-head-section school-head-section--history'])
                    ->schema([
                        Repeater::make('jobHistories')
                            ->label('Riwayat Jabatan')
                            ->relationship()
                            ->defaultItems(0)
                            ->schema([
                                DatePicker::make('started_at')->label('Tanggal Mulai')->required()->native(false),
                                DatePicker::make('ended_at')->label('Tanggal Selesai')->afterOrEqual('started_at')->native(false),
                                TextInput::make('position')->label('Jabatan')->required()->maxLength(150),
                                TextInput::make('institution_name')->label('Nama Instansi/Madrasah/Sekolah')->required()->maxLength(255),
                                Textarea::make('description')->label('Keterangan')->rows(2)->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Riwayat Jabatan')
                            ->addActionAlignment(Alignment::Start)
                            ->addAction(fn (Action $action): Action => $action
                                ->icon(Heroicon::OutlinedPlus)
                                ->outlined())
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Diklat/Pelatihan Pengembangan Kompetensi Kepala')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->iconColor('primary')
                    ->extraAttributes(['class' => 'school-head-section school-head-section--training'])
                    ->schema([
                        Repeater::make('trainings')
                            ->label('Kegiatan Pengembangan Kompetensi')
                            ->relationship()
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('name')->label('Nama Kegiatan')->required(),
                                Select::make('activity_type')->label('Jenis Kegiatan')->options(SchoolHeadTraining::TYPES)->required()->native(false),
                                TextInput::make('organizer')->label('Penyelenggara'),
                                TextInput::make('certificate_number')->label('Nomor Sertifikat/Piagam')->maxLength(180),
                                DatePicker::make('started_at')->label('Tanggal Mulai')->native(false),
                                DatePicker::make('ended_at')->label('Tanggal Selesai')->afterOrEqual('started_at')->native(false),
                                Textarea::make('description')->label('Keterangan')->rows(2)->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Kegiatan')
                            ->addActionAlignment(Alignment::Start)
                            ->addAction(fn (Action $action): Action => $action
                                ->icon(Heroicon::OutlinedPlus)
                                ->outlined())
                            ->columnSpanFull(),
                    ]),
                Section::make('Sertifikat Kompetensi Kepala Madrasah/Sekolah')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->iconColor('primary')
                    ->extraAttributes(['class' => 'school-head-section school-head-section--certificate'])
                    ->schema([
                        Repeater::make('certificates')
                            ->label('Sertifikat Kompetensi')
                            ->relationship()
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('name')->label('Nama Sertifikat')->required(),
                                TextInput::make('certificate_number')->label('Nomor Sertifikat')->maxLength(180),
                                TextInput::make('issuer')->label('Lembaga Penerbit'),
                                DatePicker::make('issued_at')->label('Tanggal Sertifikat')->native(false),
                                Textarea::make('description')->label('Keterangan')->rows(2)->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Sertifikat')
                            ->addActionAlignment(Alignment::Start)
                            ->addAction(fn (Action $action): Action => $action
                                ->icon(Heroicon::OutlinedPlus)
                                ->outlined())
                            ->columnSpanFull(),
                    ]),
                Section::make('Prestasi Kepala Madrasah/Sekolah')
                    ->icon(Heroicon::OutlinedTrophy)
                    ->iconColor('primary')
                    ->extraAttributes(['class' => 'school-head-section school-head-section--achievement'])
                    ->schema([
                        Repeater::make('achievements')
                            ->label('Prestasi/Penghargaan')
                            ->relationship()
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('name')->label('Nama Prestasi/Penghargaan')->required(),
                                Select::make('level')->label('Tingkat Prestasi')->options(SchoolHeadAchievement::LEVELS)->required()->native(false),
                                DatePicker::make('achieved_at')->label('Tanggal Prestasi')->native(false),
                                TextInput::make('organizer')->label('Penyelenggara'),
                                Textarea::make('description')->label('Keterangan')->rows(2)->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Prestasi')
                            ->addActionAlignment(Alignment::Start)
                            ->addAction(fn (Action $action): Action => $action
                                ->icon(Heroicon::OutlinedPlus)
                                ->outlined())
                            ->columnSpanFull(),
                    ]),
                Section::make('Dokumen SK Kepala')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->iconColor('primary')
                    ->extraAttributes(['class' => 'school-head-section school-head-section--document'])
                    ->description('Unggah SK Kepala terbaru dalam format PDF. Dokumen disimpan pada penyimpanan privat.')
                    ->schema([
                        FileUpload::make('latest_sk_upload')
                            ->label('Upload File SK Kepala')
                            ->disk(Document::PRIVATE_DISK)
                            ->directory(fn (?SchoolHead $record): string => $record
                                ? DocumentStorage::directoryFor($record)
                                : 'school-heads/pending')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(DocumentStorage::MAX_SIZE_KB)
                            ->storeFileNamesIn('latest_sk_original_name')
                            ->downloadable(false)
                            ->openable(false)
                            ->previewable(false)
                            ->helperText('Format PDF, maksimal 1000 MB. File dapat diunduh melalui bagian Dokumen setelah disimpan.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
