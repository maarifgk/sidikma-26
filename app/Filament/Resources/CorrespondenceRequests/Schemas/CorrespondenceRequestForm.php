<?php

namespace App\Filament\Resources\CorrespondenceRequests\Schemas;

use App\Models\CorrespondenceRequest;
use App\Models\School;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CorrespondenceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->label('ASAL MADRASAH/SEKOLAH')
                    ->placeholder('--Pilih--')
                    ->options(fn (): array => School::activeOptionsFor())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                TextInput::make('type_name')
                    ->label('JENIS SURAT')
                    ->placeholder('Ketik jenis surat')
                    ->maxLength(255)
                    ->required(),
                FileUpload::make('request_file_path')
                    ->label('UPLOAD FILE PERMOHONAN (PDF)')
                    ->disk(CorrespondenceRequest::DISK)
                    ->visibility('private')
                    ->directory('request-files')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->downloadable()
                    ->openable()
                    ->previewable(false)
                    ->required()
                    ->helperText('Format PDF, maksimal 1000 MB.')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
