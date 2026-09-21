<?php

namespace App\Filament\Resources\SipinterUpdates\Schemas;

use App\Models\School;
use App\Models\SipinterUpdate;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SipinterUpdateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->label('SEKOLAH/MADRASAH')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => School::activeOptionsFor())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        $school = School::query()->find($state);

                        $set('npsn', $school?->npsn);
                        $set('school_address', $school?->address);
                    })
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->native(false),
                TextInput::make('npsn')
                    ->label('NPSN')
                    ->placeholder('Masukan NPSN')
                    ->required()
                    ->length(8)
                    ->regex('/^[0-9]{8}$/'),
                TextInput::make('school_address')
                    ->label('ALAMAT SEKOLAH/MADRASAH')
                    ->placeholder('Masukan Alamat')
                    ->required()
                    ->maxLength(2000),
                Select::make('land_ownership')
                    ->label('SATUAN PENDIDIKAN TERSEBUT DIDIRIKAN DI ATAS TANAH')
                    ->placeholder('-- Pilih --')
                    ->options([
                        'milik_yayasan' => 'Milik Yayasan',
                        'wakaf' => 'Tanah Wakaf',
                        'pemerintah' => 'Tanah Pemerintah',
                        'pihak_lain' => 'Milik Pihak Lain',
                    ])
                    ->required()
                    ->native(false),
                Select::make('land_status')
                    ->label('STATUS TANAH TEMPAT DIBANGUN SATUAN PENDIDIKAN TERSEBUT BERUPA TANAH')
                    ->placeholder('-- Pilih --')
                    ->options([
                        'sertifikat_hak_milik' => 'Sertifikat Hak Milik',
                        'sertifikat_wakaf' => 'Sertifikat Wakaf',
                        'akta_ikrar_wakaf' => 'Akta Ikrar Wakaf',
                        'sewa' => 'Sewa',
                        'pinjam_pakai' => 'Pinjam Pakai',
                        'belum_bersertifikat' => 'Belum Bersertifikat',
                    ])
                    ->required()
                    ->native(false),
                Select::make('management_authority')
                    ->label('SATUAN PENDIDIKAN TERSEBUT BERADA DIBAWAH PENGELOLAAN')
                    ->placeholder('-- Pilih --')
                    ->options([
                        'lp_maarif_pcnu' => "LP. Ma'arif NU PCNU Gunungkidul",
                        'yayasan' => 'Yayasan',
                        'pemerintah' => 'Pemerintah',
                        'lainnya' => 'Lainnya',
                    ])
                    ->required()
                    ->native(false),
                Select::make('uses_notarial_deed')
                    ->label('SATUAN PENDIDIKAN TERSEBUT MENGGUNAKAN AKTA NOTARIS')
                    ->placeholder('-- Pilih --')
                    ->options([
                        1 => 'Ya',
                        0 => 'Tidak',
                    ])
                    ->required()
                    ->native(false),
                self::pdfUpload(
                    'request_file_path',
                    'UPLOAD FILE SURAT PERMOHONAN (PDF)',
                    'request-files',
                    required: true,
                ),
                Placeholder::make('process_information')
                    ->label('KETERANGAN:')
                    ->content(new HtmlString(
                        "Setelah selesai menginput, data dan dokumen yang masuk akan kami proses untuk menerbitkan Surat Keterangan Aset dari LP. Ma'arif NU PCNU Gunungkidul dan Surat Rekomendasi dari LP. Ma'arif PWNU D.I. Yogyakarta.",
                    ))
                    ->columnSpanFull(),
                Section::make('DOKUMEN HASIL PROSES')
                    ->description('Unggah dokumen yang telah diterbitkan agar tombol View tersedia pada halaman daftar.')
                    ->schema([
                        self::pdfUpload('asset_file_path', 'FILE ASET', 'asset-files'),
                        self::pdfUpload('recommendation_file_path', 'FILE REKOMENDASI', 'recommendation-files'),
                    ])
                    ->columns(2)
                    ->visibleOn('edit')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function pdfUpload(
        string $name,
        string $label,
        string $directory,
        bool $required = false,
    ): FileUpload {
        return FileUpload::make($name)
            ->label($label)
            ->disk(SipinterUpdate::DISK)
            ->visibility('private')
            ->directory($directory)
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize(1024000)
            ->downloadable()
            ->openable()
            ->previewable(false)
            ->required($required)
            ->helperText('Format PDF, maksimal 1000 MB.');
    }
}
