<?php

namespace App\Filament\Resources\LearningModules\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\LearningModules\LearningModuleResource;
use App\Models\Foundation;
use App\Models\LearningModule;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class ListLearningModules extends ListRecords
{
    use WithFileUploads;

    protected static string $resource = LearningModuleResource::class;

    protected static ?string $title = 'Modul';

    public string $className = '';

    public string $moduleType = '';

    public int|string $semester = 1;

    public string $subject = '';

    public string $chapter = '';

    public mixed $moduleFile = null;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.learning-modules.upload-form'),
                EmbeddedTable::make(),
            ]);
    }

    public function uploadModule(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->can('create', LearningModule::class), 403);

        $validated = $this->validate([
            'className' => ['required', 'string', 'max:100'],
            'moduleType' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'integer', 'in:1,2'],
            'subject' => ['required', 'string', 'max:255'],
            'chapter' => ['required', 'string', 'max:255'],
            'moduleFile' => [
                'required',
                'file',
                'max:1024000',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx',
            ],
        ], [], [
            'className' => 'kelas',
            'moduleType' => 'jenis modul',
            'semester' => 'semester',
            'subject' => 'mata pelajaran',
            'chapter' => 'BAB',
            'moduleFile' => 'file modul',
        ]);

        $path = $this->moduleFile->store('files', LearningModule::DISK);

        LearningModule::query()->create([
            'foundation_id' => Foundation::application()->getKey(),
            'class_name' => $validated['className'],
            'module_type' => $validated['moduleType'],
            'semester' => $validated['semester'],
            'subject' => $validated['subject'],
            'chapter' => $validated['chapter'],
            'file_path' => $path,
            'original_name' => $this->moduleFile->getClientOriginalName(),
            'uploaded_at' => now(),
            'uploaded_by' => $user->getKey(),
        ]);

        $this->reset(['className', 'moduleType', 'subject', 'chapter', 'moduleFile']);
        $this->semester = 1;
        $this->resetTable();

        Notification::make()
            ->title('Modul berhasil diunggah')
            ->success()
            ->send();
    }
}
