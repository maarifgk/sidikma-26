<?php

namespace App\Filament\Resources\EmployeeActivityRequests\Schemas;

use App\Models\Employee;
use App\Models\EmployeeActivityRequest;
use App\Models\School;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeActivityRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_name')
                    ->label('NAMA LENGKAP DAN GELAR')
                    ->placeholder('Masukan Nama Lengkap')
                    ->datalist(fn (): array => Employee::query()
                        ->where('is_active', true)
                        ->whereIn('school_id', array_keys(School::activeOptionsFor()))
                        ->orderBy('name')
                        ->pluck('name')
                        ->all())
                    ->required()
                    ->maxLength(255),
                DatePicker::make('inactive_date')
                    ->label('TANGGAL MULAI TIDAK AKTIF')
                    ->default(today())
                    ->required(),
                FileUpload::make('request_letter_path')
                    ->label('UPLOAD SURAT PERMOHONAN (PDF)')
                    ->disk(EmployeeActivityRequest::DISK)
                    ->visibility('private')
                    ->directory('request-letters')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->downloadable()
                    ->openable()
                    ->previewable(false)
                    ->required()
                    ->helperText('Format PDF, maksimal 1000 MB.'),
            ])
            ->columns(2);
    }
}
