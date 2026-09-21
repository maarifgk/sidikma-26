<?php

namespace App\Filament\Resources\ProposalRequests\Schemas;

use App\Models\ProposalRequest;
use App\Models\School;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProposalRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->label('SEKOLAH/MADRASAH')
                    ->placeholder('--Pilih--')
                    ->options(fn (): array => School::activeOptionsFor())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Select::make('proposal_type')
                    ->label('JENIS PERMOHONAN BANTUAN/PROPOSAL')
                    ->placeholder('--Pilih--')
                    ->options([
                        'Permohonan Bantuan untuk Pembangunan' => 'Permohonan Bantuan untuk Pembangunan',
                        'Permohonan Bantuan untuk Kegiatan' => 'Permohonan Bantuan untuk Kegiatan',
                    ])
                    ->native(false)
                    ->required(),
                FileUpload::make('request_file_path')
                    ->label('UPLOAD SURAT PERMOHONAN BANTUAN/PROPOSAL (PDF)')
                    ->disk(ProposalRequest::DISK)
                    ->visibility('private')
                    ->directory('request-files')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->downloadable()
                    ->openable()
                    ->previewable(false)
                    ->required()
                    ->helperText('Format PDF, maksimal 1000 MB.'),
                TextInput::make('requested_amount')
                    ->label('NOMINAL YANG DIAJUKAN')
                    ->placeholder('Masukan Nilai')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('bank_name')
                    ->label('NAMA BANK')
                    ->placeholder('Masukan Nama Bank')
                    ->required()
                    ->maxLength(255),
                TextInput::make('bank_account_number')
                    ->label('NOMOR REKENING')
                    ->placeholder('Masukan No.Rekening')
                    ->required()
                    ->maxLength(100),
                TextInput::make('bank_account_name')
                    ->label('ATAS NAMA REKENING')
                    ->placeholder('Masukan Atas Nama Rekening')
                    ->required()
                    ->maxLength(255),
            ])
            ->columns(2);
    }
}
