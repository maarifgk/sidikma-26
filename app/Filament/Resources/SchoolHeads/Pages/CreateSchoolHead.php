<?php

namespace App\Filament\Resources\SchoolHeads\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\SchoolHeads\SchoolHeadResource;
use App\Models\Document;
use App\Models\School;
use App\Models\User;
use App\Services\DocumentStorage;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class CreateSchoolHead extends CreateRecord
{
    protected static string $resource = SchoolHeadResource::class;

    protected static ?string $title = 'Lengkapi Data Kepala Madrasah/Sekolah';

    protected static bool $canCreateAnother = false;

    /** @var array{path: string, original_name: string}|null */
    private ?array $pendingSkDocument = null;

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        if ($this->pendingSkDocument === null) {
            return;
        }

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $disk = Storage::disk(Document::PRIVATE_DISK);
        $sourcePath = $this->pendingSkDocument['path'];
        $targetPath = DocumentStorage::directoryFor($this->record).'/'.basename($sourcePath);

        abort_unless($disk->exists($sourcePath) && $disk->move($sourcePath, $targetPath), 422, 'File SK Kepala gagal dipindahkan ke penyimpanan privat.');

        $attributes = app(DocumentStorage::class)->prepareCreateData([
            'document_type' => 'sk',
            'path' => $targetPath,
            'original_name' => $this->pendingSkDocument['original_name'],
        ], $this->record, $user);

        $this->record->documents()->create($attributes);
        $this->pendingSkDocument = null;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan')
            ->icon(Heroicon::OutlinedCheckCircle);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }
}
