<?php

namespace App\Filament\Resources\CorrespondenceRequests\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\ListRecords;
use App\Filament\Resources\CorrespondenceRequests\CorrespondenceRequestResource;
use App\Models\CorrespondenceType;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class ListCorrespondenceRequests extends ListRecords
{
    use HasAdministrationFormFeedback;

    protected static string $resource = CorrespondenceRequestResource::class;

    protected static ?string $title = 'PENGAJUAN PERSURATAN';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function editTypesAction(): Action
    {
        return Action::make('editTypes')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->modalHeading('Edit Jenis-Jenis Permohonan Persuratan')
            ->modalDescription('Tambah, hapus, urutkan, atau ubah jenis dan keterangan persuratan.')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->modalWidth(Width::SevenExtraLarge)
            ->visible(fn (): bool => auth()->user()?->can('document.update') ?? false)
            ->fillForm(fn (): array => [
                'items' => CorrespondenceType::query()
                    ->ordered()
                    ->get()
                    ->map(fn (CorrespondenceType $type): array => [
                        'id' => $type->getKey(),
                        'number' => $type->number,
                        'name' => $type->name,
                        'template_label' => $type->template_label,
                        'template_path' => $type->template_path,
                        'is_selectable' => $type->is_selectable,
                        'is_active' => $type->is_active,
                    ])
                    ->all(),
            ])
            ->schema([
                Repeater::make('items')
                    ->label('Jenis dan Keterangan Persuratan')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('number')
                            ->label('Nomor/Label')
                            ->placeholder('Contoh: 1. atau KET:')
                            ->maxLength(20)
                            ->columnSpan(1),
                        Textarea::make('name')
                            ->label('Jenis/Keterangan')
                            ->required()
                            ->rows(2)
                            ->maxLength(5000)
                            ->columnSpan(4),
                        FileUpload::make('template_path')
                            ->label('File Template Word')
                            ->preserveFilenames()
                            ->disk('correspondence-requests')
                            ->directory('templates')
                            ->acceptedFileTypes([
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(819200)
                            ->downloadable()
                            ->helperText('Format DOC atau DOCX, maksimal 800 MB.')
                            ->columnSpan(5),
                        Toggle::make('is_selectable')
                            ->label('Dapat dipilih')
                            ->default(true)
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label('Ditampilkan')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(7)
                    ->defaultItems(0)
                    ->addActionLabel('Tambah Jenis/Keterangan')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(
                        fn (array $state): string => str($state['name'] ?? 'Jenis baru')
                            ->limit(80)
                            ->toString(),
                    )
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $this->syncTypes($data['items'] ?? []);

                Notification::make()
                    ->title('Jenis persuratan berhasil diperbarui')
                    ->success()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->url(CorrespondenceRequestResource::getUrl('create', isAbsolute: false)),
        ];
    }

    private function syncTypes(array $items): void
    {
        DB::transaction(function () use ($items): void {
            $keptIds = [];

            foreach (array_values($items) as $index => $item) {
                $type = filled($item['id'] ?? null)
                    ? CorrespondenceType::query()->findOrFail($item['id'])
                    : new CorrespondenceType(['created_by' => auth()->id()]);

                $type->fill([
                    'position' => $index + 1,
                    'number' => filled($item['number'] ?? null) ? $item['number'] : null,
                    'name' => $item['name'],
                    'template_label' => filled($item['template_path'] ?? null) ? 'Download Word' : null,
                    'template_path' => filled($item['template_path'] ?? null)
                        ? $item['template_path']
                        : null,
                    'is_selectable' => (bool) ($item['is_selectable'] ?? false),
                    'is_active' => (bool) ($item['is_active'] ?? false),
                    'updated_by' => auth()->id(),
                ])->save();

                $keptIds[] = $type->getKey();
            }

            CorrespondenceType::query()
                ->when(
                    $keptIds !== [],
                    fn ($query) => $query->whereNotIn('id', $keptIds),
                )
                ->delete();
        });
    }
}
