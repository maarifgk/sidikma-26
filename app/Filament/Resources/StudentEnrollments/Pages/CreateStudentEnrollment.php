<?php

namespace App\Filament\Resources\StudentEnrollments\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;

class CreateStudentEnrollment extends CreateRecord
{
    protected static string $resource = StudentEnrollmentResource::class;

    protected static ?string $title = 'Tambah Data Jumlah Siswa';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        School::query()->accessibleTo($user)->findOrFail($data['school_id']);

        $data['submitted_by'] = $user->getKey();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
