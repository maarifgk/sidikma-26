<?php

namespace App\Filament\Resources\SipinterUpdates\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\ListRecords;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use App\Models\SipinterRequirement;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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

class ListSipinterUpdates extends ListRecords
{
    use HasAdministrationFormFeedback;

    protected static string $resource = SipinterUpdateResource::class;

    protected static ?string $title = 'Update Data Sipinter';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.sipinter-updates.requirements-panel')
                    ->viewData(fn (): array => [
                        'requirements' => SipinterRequirement::query()
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
            ->modalHeading('Edit Kelengkapan Update Data Sipinter')
            ->modalDescription('Tambah, hapus, urutkan, atau ubah kelengkapan dan persyaratan.')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->modalWidth(Width::SevenExtraLarge)
            ->visible(fn (): bool => auth()->user()?->can('document.update') ?? false)
            ->fillForm(fn (): array => [
                'items' => SipinterRequirement::query()
                    ->ordered()
                    ->get()
                    ->map(fn (SipinterRequirement $requirement): array => [
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
                    ->label('Kelengkapan dan Persyaratan')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('number')
                            ->label('Nomor/Label')
                            ->placeholder('Contoh: 1. atau KET:')
                            ->maxLength(20)
                            ->columnSpan(1),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->required()
                            ->rows(2)
                            ->maxLength(5000)
                            ->columnSpan(4),
                        FileUpload::make('template_path')
                            ->label('File Template Word')
                            ->preserveFilenames()
                            ->disk('sipinter-updates')
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
                    ->addActionLabel('Tambah Kelengkapan/Persyaratan')
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
                    ->title('Kelengkapan Sipinter berhasil diperbarui')
                    ->success()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Input Data')
                ->icon('heroicon-o-plus')
                ->mutateDataUsing(function (array $data): array {
                    $user = auth()->user();
                    abort_unless($user instanceof User, 403);

                    $data['uploaded_by'] = $user->getKey();

                    return $data;
                }),
        ];
    }

    private function syncRequirements(array $items): void
    {
        DB::transaction(function () use ($items): void {
            $keptIds = [];

            foreach (array_values($items) as $index => $item) {
                $requirement = filled($item['id'] ?? null)
                    ? SipinterRequirement::query()->findOrFail($item['id'])
                    : new SipinterRequirement(['created_by' => auth()->id()]);

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

            SipinterRequirement::query()
                ->when(
                    $keptIds !== [],
                    fn ($query) => $query->whereNotIn('id', $keptIds),
                )
                ->delete();
        });
    }
}
