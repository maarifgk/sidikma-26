<?php

namespace App\Filament\Resources\FoundationSkTemplates\Pages;

use App\Filament\Resources\FoundationSkTemplates\FoundationSkTemplateResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditFoundationSkTemplate extends EditRecord
{
    protected static string $resource = FoundationSkTemplateResource::class;
    protected static ?string $title = 'Edit Template';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPdf')
                ->label('Lihat PDF')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => route('foundation-sk.templates.preview', ['template' => $this->record->getKey()]))
                ->openUrlInNewTab(),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui isi template dan gunakan Preview untuk melihat hasil tanpa menyimpan dokumen.';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['html_template'] = $data['html_template'] ?? $this->record->html_template ?? '';

        return $data;
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()->label('Simpan Template');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()->label('Kembali')->color('success');
    }
}
