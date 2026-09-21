<?php

namespace App\Filament\Resources\StudentEnrollments\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use Filament\Actions\DeleteAction;

class EditStudentEnrollment extends EditRecord
{
    protected static string $resource = StudentEnrollmentResource::class;

    protected static ?string $title = 'Edit Data Jumlah Siswa';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
