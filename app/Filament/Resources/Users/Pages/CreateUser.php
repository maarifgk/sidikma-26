<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\Users\UserResource;
use App\Services\SchoolAdminMembershipService;
use Filament\Actions\Action;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Tambah Admin';

    protected static bool $canCreateAnother = false;

    private ?string $selectedRoleName = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedRoleName = $data['role_name'] ?? null;
        unset($data['role_name']);

        $data['is_active'] = true;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if (filled($this->selectedRoleName)) {
            $this->getRecord()->syncRoles([$this->selectedRoleName]);
        }

        app(SchoolAdminMembershipService::class)->sync($this->getRecord());
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali');
    }
}
