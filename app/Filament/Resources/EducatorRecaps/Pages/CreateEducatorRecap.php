<?php

namespace App\Filament\Resources\EducatorRecaps\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\EducatorRecaps\EducatorRecapResource;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;

class CreateEducatorRecap extends CreateRecord
{
    protected static string $resource = EducatorRecapResource::class;

    protected static ?string $title = 'Tambah Data Jumlah Tenaga Pendidik';

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
