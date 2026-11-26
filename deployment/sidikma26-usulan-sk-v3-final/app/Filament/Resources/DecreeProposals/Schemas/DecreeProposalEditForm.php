<?php

namespace App\Filament\Resources\DecreeProposals\Schemas;

use App\Models\Employee;
use App\Models\EmployeePosition;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class DecreeProposalEditForm
{
    /** @return array<int, mixed> */
    public static function schema(): array
    {
        return [
            Hidden::make('edit_version')->required(),
            TextInput::make('name')->label('Nama Lengkap')->required()->maxLength(255),
            TextInput::make('employee_code')->label('EWANUGK')->required()->maxLength(50)
                ->regex('/^[A-Z0-9._\/-]+$/i'),
            TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
            TextInput::make('phone')->label('Nomor Telepon')->tel()->required()->maxLength(30),
            TextInput::make('partner_admin_number')->label('Nomor Mitra Admin')->nullable()->maxLength(50),
            Select::make('employment_status')->label('Status Kepegawaian')
                ->options(Employee::employmentStatusOptions())->required()->live()->native(false)
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (! Employee::isPnsStatus($state)) {
                        $set('nip', null);
                        $set('rank', null);
                        $set('grade', null);
                    }
                }),
            TextInput::make('nip')->label('NIP')->length(18)->regex('/^[0-9]{18}$/')
                ->required(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status')))
                ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
            TextInput::make('rank')->label('Pangkat')->maxLength(100)
                ->required(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status')))
                ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
            TextInput::make('grade')->label('Golongan')->maxLength(50)
                ->required(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status')))
                ->visible(fn (Get $get): bool => Employee::isPnsStatus($get('employment_status'))),
            TextInput::make('birth_place')->label('Tempat Lahir')->required()->maxLength(100),
            DatePicker::make('birth_date')->label('Tanggal Lahir')->required()->maxDate(now()),
            Select::make('position_id')->label('Ketugasan')->required()->searchable()->preload()->native(false)
                ->options(fn (): array => EmployeePosition::query()->where('is_active', true)
                    ->orderBy('name')->pluck('name', 'id')->all()),
            DatePicker::make('assignment_start_date')->label('TMT Pertama')->required(),
            TextInput::make('last_education')->label('Pendidikan Terakhir, Tahun Lulus')->required()->maxLength(100),
            TextInput::make('nuptk')->label('NUPTK/NPK')->nullable()->maxLength(30)
                ->regex('/^[A-Z0-9._\/-]+$/i'),
            TextInput::make('program_study')->label('Program Studi')->required()->maxLength(150),
            Textarea::make('notes')->label('Catatan Pengajuan')->nullable()->rows(4)->maxLength(65535)
                ->columnSpanFull(),
        ];
    }
}
