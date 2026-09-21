<?php

namespace App\Filament\Resources\DecreeCorrectionRequests\Schemas;

use App\Models\DecreeCorrectionRequest;
use App\Models\School;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DecreeCorrectionRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $editable = fn (?DecreeCorrectionRequest $record): bool => auth()->user()?->isAdminInduk()
            || ! $record
            || in_array($record->status, [DecreeCorrectionRequest::STATUS_DRAFT, DecreeCorrectionRequest::STATUS_REVISION], true);

        return $schema->components([
            TextInput::make('request_number')->label('Nomor Pengajuan')->disabled()->placeholder('Dibuat otomatis'),
            DatePicker::make('request_date')->label('Tanggal Pengajuan')->default(today())->required()->disabled(),
            Select::make('school_id')->label('Asal Sekolah/Madrasah')->options(fn (): array => School::query()->whereIn('id', auth()->user()?->accessibleSchoolIds() ?? [])->pluck('name', 'id')->all())->default(fn () => auth()->user()?->accessibleSchoolIds()->first())->disabled()->dehydrated(),
            TextInput::make('decree_number')->label('Nomor SK')->required()->maxLength(150)->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record)),
            DatePicker::make('decree_date')->label('Tanggal SK')->required()->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record)),
            TextInput::make('subject_name')->label('Nama yang Tercantum pada SK')->required()->maxLength(255)->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record)),
            Select::make('correction_part')->label('Jenis/Bagian Perbaikan')->options(DecreeCorrectionRequest::correctionPartOptions())->required()->native(false)->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record)),
            Textarea::make('old_data')->label('Data Lama / Data Salah')->required()->rows(4)->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record)),
            Textarea::make('new_data')->label('Data Baru / Data Benar')->required()->rows(4)->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record)),
            Textarea::make('reason')->label('Alasan/Keterangan Perbaikan')->required()->rows(4)->columnSpanFull()->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record)),
            FileUpload::make('old_decree_path')->label('Upload SK Lama')->disk('documents')->directory('decree-corrections/old')->acceptedFileTypes(['application/pdf'])->maxSize(1024000)->required()->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record))->helperText('PDF maksimal 1000 MB.'),
            FileUpload::make('supporting_document_path')->label('Dokumen Pendukung')->disk('documents')->directory('decree-corrections/supporting')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(1024000)->disabled(fn (?DecreeCorrectionRequest $record): bool => ! $editable($record))->helperText('PDF, JPG, atau PNG maksimal 1000 MB.'),
        ])->columns(2);
    }
}
