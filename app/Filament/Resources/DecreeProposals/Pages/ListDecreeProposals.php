<?php

namespace App\Filament\Resources\DecreeProposals\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\ListRecords;
use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Models\DecreeRequirement;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class ListDecreeProposals extends ListRecords
{
    use HasAdministrationFormFeedback;

    protected static string $resource = DecreeProposalResource::class;

    protected static ?string $title = 'USULAN SK GURU DAN PEGAWAI BARU';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.decree-proposals.requirements-panel')
                    ->viewData(fn (): array => [
                        'requirements' => DecreeRequirement::query()
                            ->where('is_active', true)
                            ->ordered()
                            ->get(),
                    ]),
                EmbeddedTable::make(),
            ]);
    }

    public function editRequirementsAction(): Action
    {
        return Action::make('editRequirements')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->modalHeading('Edit Kelengkapan Usulan SK Baru')
            ->modalDescription('Tambah, hapus, urutkan, atau ubah keterangan kelengkapan.')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->modalWidth(Width::SevenExtraLarge)
            ->visible(fn (): bool => auth()->user()?->can('approval.update') ?? false)
            ->fillForm(fn (): array => [
                'items' => DecreeRequirement::query()
                    ->ordered()
                    ->get()
                    ->map(fn (DecreeRequirement $requirement): array => [
                        'id' => $requirement->getKey(),
                        'number' => $requirement->number,
                        'description' => $requirement->description,
                        'template_label' => $requirement->template_label,
                        'template_path' => $requirement->template_path,
                        'is_active' => $requirement->is_active,
                    ])
                    ->all(),
            ])
            ->schema([
                Repeater::make('items')
                    ->label('Kolom Kelengkapan')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('number')
                            ->label('Nomor')
                            ->placeholder('Contoh: 1.')
                            ->maxLength(20)
                            ->columnSpan(1),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->required()
                            ->rows(2)
                            ->maxLength(5000)
                            ->columnSpan(5),
                        FileUpload::make('template_path')
                            ->label('File Template Word')
                            ->preserveFilenames()
                            ->disk('decree-proposals')
                            ->directory('templates')
                            ->acceptedFileTypes([
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(819200)
                            ->downloadable()
                            ->helperText('Format DOC atau DOCX, maksimal 800 MB.')
                            ->columnSpan(5),
                        Toggle::make('is_active')
                            ->label('Ditampilkan')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(6)
                    ->defaultItems(0)
                    ->addActionLabel('Tambah Kolom Kelengkapan')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(
                        fn (array $state): string => str($state['description'] ?? 'Kelengkapan baru')
                            ->limit(80)
                            ->toString(),
                    )
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $this->syncRequirements($data['items'] ?? []);

                Notification::make()
                    ->title('Kelengkapan berhasil diperbarui')
                    ->success()
                    ->send();
            });
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->url(DecreeProposalResource::getUrl('create', isAbsolute: false)),
        ];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function syncRequirements(array $items): void
    {
        DB::transaction(function () use ($items): void {
            $keptIds = [];

            foreach (array_values($items) as $index => $item) {
                $requirement = filled($item['id'] ?? null)
                    ? DecreeRequirement::query()->findOrFail($item['id'])
                    : new DecreeRequirement(['created_by' => auth()->id()]);

                $requirement->fill([
                    'position' => $index + 1,
                    'number' => filled($item['number'] ?? null) ? $item['number'] : null,
                    'description' => $item['description'],
                    'template_label' => filled($item['template_path'] ?? null) ? 'Download Word' : null,
                    'template_path' => filled($item['template_path'] ?? null)
                        ? $item['template_path']
                        : null,
                    'is_active' => (bool) ($item['is_active'] ?? false),
                    'updated_by' => auth()->id(),
                ])->save();

                $keptIds[] = $requirement->getKey();
            }

            DecreeRequirement::query()
                ->when(
                    $keptIds !== [],
                    fn ($query) => $query->whereNotIn('id', $keptIds),
                )
                ->delete();
        });
    }
}
