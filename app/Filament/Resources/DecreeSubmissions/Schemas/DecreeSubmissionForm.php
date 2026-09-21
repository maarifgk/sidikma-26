<?php

namespace App\Filament\Resources\DecreeSubmissions\Schemas;

use App\Models\DecreeSubmission;
use App\Models\DecreeSubmissionType;
use App\Models\Employee;
use App\Models\School;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DecreeSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('submission_number')->label('Nomor Pengajuan')->disabled()->placeholder('Dibuat otomatis oleh sistem'),
            DatePicker::make('submission_date')->label('Tanggal Pengajuan')->default(today())->required()->disabled(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
            Select::make('school_id')->label('Sekolah/Madrasah')->options(fn (): array => School::query()->whereIn('id', auth()->user()?->accessibleSchoolIds() ?? [])->orderBy('name')->pluck('name', 'id')->all())->default(fn (): mixed => auth()->user()?->accessibleSchoolIds()->first())->disabled()->dehydrated(false),
            Select::make('employee_id')->label('Nama Guru')->options(fn (): array => Employee::query()->where('employee_type', Employee::TYPE_GURU)->where('is_active', true)->whereIn('school_id', auth()->user()?->accessibleSchoolIds() ?? [])->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->required()->disabled(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
            Select::make('decree_submission_type_id')->label('Jenis SK')->options(fn (): array => DecreeSubmissionType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->required()->disabled(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
            Textarea::make('purpose')->label('Keterangan / Keperluan')->rows(4)->maxLength(3000)->disabled(fn (): bool => auth()->user()?->isAdminInduk() ?? false)->columnSpanFull(),
            Select::make('status')->label('Status Pengajuan')->options(DecreeSubmission::statusOptions())->default(DecreeSubmission::STATUS_UNDER_REVIEW)->required()->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)->native(false),
            Textarea::make('admin_notes')->label('Catatan Admin Induk')->rows(4)->maxLength(3000)->required(fn (Get $get): bool => in_array($get('status'), [DecreeSubmission::STATUS_REJECTED, DecreeSubmission::STATUS_REVISION_REQUIRED], true))->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)->columnSpanFull(),
            FileUpload::make('result_file_path')->label('File SK Hasil')->disk('documents')->visibility('private')->directory('decree-submission-results')->acceptedFileTypes(['application/pdf'])->maxSize(1024000)->required(fn (Get $get): bool => $get('status') === DecreeSubmission::STATUS_COMPLETED)->downloadable(false)->openable(false)->previewable(false)->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)->helperText('PDF maksimal 1000 MB.'),
        ])->columns(2);
    }
}
