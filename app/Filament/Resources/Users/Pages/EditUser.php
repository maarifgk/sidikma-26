<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\SchoolAdminMembershipService;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['password'] = null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->getRecord()->is(auth()->user())) {
            $this->getRecord()->forceFill(['is_active' => true])->save();
            $this->getRecord()->syncRoles([User::ROLE_ADMIN_INDUK]);
        }

        app(SchoolAdminMembershipService::class)->sync($this->getRecord());
    }
}
