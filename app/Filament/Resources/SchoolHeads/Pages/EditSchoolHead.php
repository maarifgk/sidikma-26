<?php

namespace App\Filament\Resources\SchoolHeads\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\SchoolHeads\SchoolHeadResource;
use App\Models\School;
use App\Models\User;
use App\Services\DocumentStorage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;

class EditSchoolHead extends EditRecord
{
    protected static string $resource = SchoolHeadResource::class;

    protected static ?string $title = 'Edit Data Kepala Madrasah/Sekolah';

    /** @var array{path: string, original_name: string}|null */
    private ?array $pendingSkDocument = null;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User && School::query()->accessibleTo($user)->whereKey($data['school_id'])->exists(), 403);

        $path = $data['latest_sk_upload'] ?? null;
        $originalName = $data['latest_sk_original_name'] ?? null;

        if (is_string($path) && filled($path) && is_string($originalName) && filled($originalName)) {
            $this->pendingSkDocument = [
                'path' => $path,
                'original_name' => $originalName,
            ];
        }

        unset($data['latest_sk_upload'], $data['latest_sk_original_name']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingSkDocument === null) {
            return;
        }

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $attributes = app(DocumentStorage::class)->prepareCreateData([
            'document_type' => 'sk',
            ...$this->pendingSkDocument,
        ], $this->record, $user);

        $this->record->documents()->create($attributes);
        $this->pendingSkDocument = null;
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan')
            ->icon(Heroicon::OutlinedCheckCircle);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }
}
