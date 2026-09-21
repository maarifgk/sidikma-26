<?php

namespace App\Filament\Admin\Pages;

use App\Models\DecreeTemplate;
use App\Models\Document;
use App\Models\School;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;
use UnitEnum;
use ZipArchive;

class FoundationDecrees extends Page
{
    use WithFileUploads, WithPagination;

    private const DOCUMENT_PREFIX = 'foundation_decree:';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'SK Yayasan';

    protected static ?string $slug = 'sk-yayasan';

    protected static bool $shouldRegisterNavigation = false;

    public string $userSearch = '';

    public ?int $selectedUserId = null;

    public string $decreeYear = '';

    public string $decreeKind = '';

    public string $decreeNumber = '';

    public string $decreeDate = '';

    public string $decreeNotes = '';

    public ?int $editingDocumentId = null;

    public ?int $decreeTemplateId = null;

    public mixed $decreeFile = null;

    public string $documentSearch = '';

    public string $documentYearFilter = '';

    public string $documentKindFilter = '';

    public ?int $documentSchoolFilter = null;

    public bool $showTemplateForm = false;

    public ?int $editingTemplateId = null;

    public string $templateName = '';

    public string $templatePaperSize = 'A4';

    public string $templateOrientation = 'portrait';

    public bool $templateIsActive = true;

    public mixed $decreeTemplateFile = null;

    public string $bulkYear = '';

    public ?int $bulkTemplateId = null;

    public ?int $bulkSchoolId = null;

    /** @var array<int, mixed> */
    public array $bulkFiles = [];

    /** @var array{imported: int, skipped: int, unmatched: array<int, string>}|null */
    public ?array $bulkImportSummary = null;

    public function mount(): void
    {
        $this->decreeYear = now()->format('Y');
        $this->bulkYear = now()->format('Y');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminInduk()
            && auth()->user()->can('document.view');
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.pages.foundation-decrees')
                ->viewData(fn (): array => $this->pageData()),
        ]);
    }

    public function selectUser(int $userId): void
    {
        $user = User::query()->where('is_active', true)->findOrFail($userId);

        $this->selectedUserId = $user->getKey();
        $this->userSearch = '';
    }

    public function clearSelectedUser(): void
    {
        $this->selectedUserId = null;
        $this->userSearch = '';
    }

    public function uploadDecree(): void
    {
        Gate::authorize('create', Document::class);

        $validated = $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'decreeKind' => ['required', 'string', 'max:150'],
            'decreeNumber' => ['required', 'string', 'max:180'],
            'decreeDate' => ['required', 'date'],
            'decreeNotes' => ['nullable', 'string', 'max:2000'],
            'decreeTemplateId' => ['nullable', 'integer', 'exists:decree_templates,id'],
            'decreeFile' => ['required', 'file', 'mimes:pdf', 'max:1024000'],
        ], [
            'selectedUserId.required' => 'Cari dan pilih user terlebih dahulu.',
            'decreeFile.required' => 'Pilih file SK berformat PDF.',
        ]);

        $user = User::query()->findOrFail($validated['selectedUserId']);
        $file = $validated['decreeFile'];
        $year = date('Y', strtotime($validated['decreeDate']));
        $path = $file->store(
            'foundation-decrees/'.$year,
            Document::PRIVATE_DISK,
        );

        abort_unless(filled($path), 500, 'File SK gagal disimpan.');

        try {
            Document::query()->create([
                'document_type' => $this->documentType(
                    $year,
                    $validated['decreeTemplateId'] ?? null,
                ),
                'decree_kind' => $validated['decreeKind'],
                'decree_number' => $validated['decreeNumber'],
                'decree_date' => $validated['decreeDate'],
                'notes' => $validated['decreeNotes'] ?: null,
                'owner_type' => User::class,
                'owner_id' => $user->getKey(),
                'disk' => Document::PRIVATE_DISK,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/pdf',
                'size' => $file->getSize(),
                'checksum' => hash_file('sha256', Storage::disk(Document::PRIVATE_DISK)->path($path)),
                'status' => Document::STATUS_ACTIVE,
                'uploaded_by' => auth()->id(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk(Document::PRIVATE_DISK)->delete($path);

            throw $exception;
        }

        $this->reset('selectedUserId', 'userSearch', 'decreeKind', 'decreeNumber', 'decreeDate', 'decreeNotes', 'decreeTemplateId', 'decreeFile');
        $this->decreeYear = now()->format('Y');

        Notification::make()
            ->success()
            ->title('File SK berhasil diunggah')
            ->body("Dokumen telah dihubungkan ke {$user->name}.")
            ->send();
    }

    public function editDecree(int $documentId): void
    {
        $document = Document::query()
            ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
            ->findOrFail($documentId);
        Gate::authorize('update', $document);

        $this->editingDocumentId = $document->getKey();
        $this->selectedUserId = (int) $document->owner_id;
        $this->decreeKind = (string) $document->decree_kind;
        $this->decreeNumber = (string) $document->decree_number;
        $this->decreeDate = $document->decree_date?->format('Y-m-d') ?? '';
        $this->decreeNotes = (string) $document->notes;
        $this->decreeYear = $this->documentYear($document->document_type);
        $this->decreeTemplateId = $this->documentTemplateId($document->document_type);
        $this->decreeFile = null;
        $this->resetValidation();
    }

    public function cancelEditDecree(): void
    {
        $this->reset('editingDocumentId', 'selectedUserId', 'userSearch', 'decreeKind', 'decreeNumber', 'decreeDate', 'decreeNotes', 'decreeTemplateId', 'decreeFile');
        $this->decreeYear = now()->format('Y');
        $this->resetValidation();
    }

    public function updateDecree(): void
    {
        $document = Document::query()
            ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
            ->findOrFail($this->editingDocumentId);
        Gate::authorize('update', $document);

        $validated = $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'decreeKind' => ['required', 'string', 'max:150'],
            'decreeNumber' => ['required', 'string', 'max:180'],
            'decreeDate' => ['required', 'date'],
            'decreeNotes' => ['nullable', 'string', 'max:2000'],
            'decreeTemplateId' => ['nullable', 'integer', 'exists:decree_templates,id'],
            'decreeFile' => ['nullable', 'file', 'mimes:pdf', 'max:1024000'],
        ]);

        $year = date('Y', strtotime($validated['decreeDate']));
        $oldPath = $document->path;
        $newPath = $this->decreeFile?->store('foundation-decrees/'.$year, Document::PRIVATE_DISK);

        $document->fill([
            'document_type' => $this->documentType($year, $validated['decreeTemplateId'] ?? null),
            'owner_id' => $validated['selectedUserId'],
            'decree_kind' => $validated['decreeKind'],
            'decree_number' => $validated['decreeNumber'],
            'decree_date' => $validated['decreeDate'],
            'notes' => $validated['decreeNotes'] ?: null,
        ]);

        if ($newPath) {
            $document->fill([
                'path' => $newPath,
                'original_name' => $this->decreeFile->getClientOriginalName(),
                'mime_type' => $this->decreeFile->getMimeType() ?: 'application/pdf',
                'size' => $this->decreeFile->getSize(),
                'checksum' => hash_file('sha256', Storage::disk(Document::PRIVATE_DISK)->path($newPath)),
            ]);
        }

        try {
            $document->save();
        } catch (\Throwable $exception) {
            if ($newPath) Storage::disk(Document::PRIVATE_DISK)->delete($newPath);
            throw $exception;
        }

        if ($newPath && $oldPath !== $newPath) Storage::disk(Document::PRIVATE_DISK)->delete($oldPath);
        $this->cancelEditDecree();

        Notification::make()->success()->title('Data SK berhasil diperbarui')->send();
    }

    public function deleteDecree(int $documentId): void
    {
        $document = Document::query()
            ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
            ->findOrFail($documentId);
        Gate::authorize('delete', $document);

        Storage::disk(Document::PRIVATE_DISK)->delete($document->path);
        $document->delete();

        Notification::make()->success()->title('Data SK berhasil dihapus')->send();
    }

    public function openCreateTemplate(): void
    {
        $this->resetTemplateForm();
        $this->showTemplateForm = true;
    }

    public function openEditTemplate(int $templateId): void
    {
        $template = DecreeTemplate::query()->findOrFail($templateId);

        $this->editingTemplateId = $template->getKey();
        $this->templateName = $template->name;
        $this->templatePaperSize = $template->paper_size;
        $this->templateOrientation = $template->orientation;
        $this->templateIsActive = $template->is_active;
        $this->decreeTemplateFile = null;
        $this->showTemplateForm = true;
        $this->resetValidation();
    }

    public function cancelTemplateForm(): void
    {
        $this->resetTemplateForm();
    }

    public function saveTemplate(): void
    {
        Gate::authorize('create', Document::class);

        $wasEditing = $this->editingTemplateId !== null;
        $fileRule = $this->editingTemplateId === null ? 'required' : 'nullable';
        $validated = $this->validate([
            'templateName' => ['required', 'string', 'max:255'],
            'templatePaperSize' => ['required', 'in:'.implode(',', DecreeTemplate::PAPER_SIZES)],
            'templateOrientation' => ['required', 'in:'.implode(',', DecreeTemplate::ORIENTATIONS)],
            'templateIsActive' => ['boolean'],
            'decreeTemplateFile' => [$fileRule, 'file', 'mimes:pdf,doc,docx', 'max:1024000'],
        ], [
            'decreeTemplateFile.required' => 'Pilih file template PDF, DOC, atau DOCX.',
        ]);

        $template = $this->editingTemplateId
            ? DecreeTemplate::query()->findOrFail($this->editingTemplateId)
            : new DecreeTemplate;
        $oldPath = $template->exists ? $template->path : null;
        $newPath = null;

        if ($this->decreeTemplateFile) {
            $newPath = $this->decreeTemplateFile->store('foundation-decree-templates', Document::PRIVATE_DISK);
            abort_unless(filled($newPath), 500, 'File template gagal disimpan.');
        }

        try {
            $template->fill([
                'name' => $validated['templateName'],
                'paper_size' => $validated['templatePaperSize'],
                'orientation' => $validated['templateOrientation'],
                'is_active' => $validated['templateIsActive'],
                'updated_by' => auth()->id(),
            ]);

            if (! $template->exists) {
                $template->created_by = auth()->id();
            }

            if ($newPath) {
                $template->fill([
                    'disk' => Document::PRIVATE_DISK,
                    'path' => $newPath,
                    'original_name' => $this->decreeTemplateFile->getClientOriginalName(),
                    'mime_type' => $this->decreeTemplateFile->getMimeType(),
                    'size' => $this->decreeTemplateFile->getSize(),
                ]);
            }

            $template->save();
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk(Document::PRIVATE_DISK)->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && $oldPath && $oldPath !== $newPath) {
            Storage::disk(Document::PRIVATE_DISK)->delete($oldPath);
        }

        $this->resetTemplateForm();

        Notification::make()
            ->success()
            ->title($wasEditing ? 'Template SK diperbarui' : 'Template SK ditambahkan')
            ->send();
    }

    public function generateTemplate(int $templateId): mixed
    {
        Gate::authorize('create', Document::class);

        if (! $this->selectedUserId) {
            Notification::make()
                ->warning()
                ->title('Pilih user terlebih dahulu')
                ->body('Gunakan pencarian pada Upload SK Per User, lalu klik user tujuan.')
                ->send();

            return null;
        }

        $template = DecreeTemplate::query()->where('is_active', true)->findOrFail($templateId);
        $user = User::query()->with(['employee.school', 'memberships.school'])->findOrFail($this->selectedUserId);
        $disk = Storage::disk(Document::PRIVATE_DISK);
        abort_unless($template->disk === Document::PRIVATE_DISK && $disk->exists($template->path), 404);

        $extension = strtolower(pathinfo($template->original_name, PATHINFO_EXTENSION));
        $downloadName = 'SK-'.Str::slug($user->name).'-'.$this->decreeYear.'.'.$extension;

        if ($extension !== 'docx') {
            return $disk->download($template->path, $downloadName, [
                'Content-Type' => $template->mime_type ?: 'application/octet-stream',
            ]);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'sk-template-');
        abort_unless($temporaryPath !== false, 500, 'File sementara gagal dibuat.');
        copy($disk->path($template->path), $temporaryPath);

        $zip = new ZipArchive;
        abort_unless($zip->open($temporaryPath) === true, 422, 'Template DOCX tidak valid.');
        $xml = $zip->getFromName('word/document.xml');
        abort_unless(is_string($xml), 422, 'Isi template DOCX tidak ditemukan.');

        $school = $user->employee?->school
            ?? $user->memberships->firstWhere('school_id', '!=', null)?->school;
        $employee = $user->employee;
        $replacements = [
            '{{nama}}' => $user->name,
            '{{email}}' => $user->email,
            '{{ewanugk}}' => $employee?->employee_code ?? '',
            '{{nik}}' => $employee?->nik ?? '',
            '{{nip}}' => $employee?->nip ?? '',
            '{{nuptk}}' => $employee?->nuptk ?? '',
            '{{sekolah}}' => $school?->name ?? '',
            '{{tahun}}' => $this->decreeYear,
        ];
        $escapedReplacements = array_map(
            fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            $replacements,
        );
        $zip->addFromString('word/document.xml', str_replace(
            array_keys($escapedReplacements),
            array_values($escapedReplacements),
            $xml,
        ));
        $zip->close();

        return response()->download($temporaryPath, $downloadName)->deleteFileAfterSend(true);
    }

    public function importBulkDecrees(): void
    {
        Gate::authorize('create', Document::class);

        $validated = $this->validate([
            'bulkYear' => ['required', 'digits:4', 'integer', 'min:2000', 'max:2100'],
            'bulkTemplateId' => ['nullable', 'integer', 'exists:decree_templates,id'],
            'bulkSchoolId' => ['nullable', 'integer', 'exists:schools,id'],
            'bulkFiles' => ['required', 'array', 'min:1', 'max:100'],
            'bulkFiles.*' => ['required', 'file', 'mimes:pdf', 'max:1024000'],
        ], [
            'bulkFiles.required' => 'Pilih minimal satu file SK berformat PDF.',
        ]);

        $users = User::query()
            ->where('is_active', true)
            ->when($validated['bulkSchoolId'] ?? null, function (Builder $query, int $schoolId): void {
                $query->where(function (Builder $query) use ($schoolId): void {
                    $query
                        ->whereHas('employee', fn (Builder $query): Builder => $query->where('school_id', $schoolId))
                        ->orWhereHas('memberships', fn (Builder $query): Builder => $query
                            ->active()
                            ->where('school_id', $schoolId));
                });
            })
            ->with(['employee.school', 'memberships.school'])
            ->get();

        $imported = 0;
        $skipped = 0;
        $unmatched = [];

        foreach ($validated['bulkFiles'] as $file) {
            $user = $this->matchUserForFile($file->getClientOriginalName(), $users);

            if (! $user) {
                $unmatched[] = $file->getClientOriginalName();

                continue;
            }

            $path = $file->store('foundation-decrees/'.$validated['bulkYear'], Document::PRIVATE_DISK);
            abort_unless(filled($path), 500, 'Salah satu file SK gagal disimpan.');
            $checksum = hash_file('sha256', Storage::disk(Document::PRIVATE_DISK)->path($path));
            $documentType = $this->documentType(
                (string) $validated['bulkYear'],
                $validated['bulkTemplateId'] ?? null,
            );

            $duplicateExists = Document::query()
                ->where('owner_type', User::class)
                ->where('owner_id', $user->getKey())
                ->where('document_type', $documentType)
                ->where('checksum', $checksum)
                ->where('status', Document::STATUS_ACTIVE)
                ->exists();

            if ($duplicateExists) {
                Storage::disk(Document::PRIVATE_DISK)->delete($path);
                $skipped++;

                continue;
            }

            try {
                Document::query()->create([
                    'document_type' => $documentType,
                    'owner_type' => User::class,
                    'owner_id' => $user->getKey(),
                    'disk' => Document::PRIVATE_DISK,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/pdf',
                    'size' => $file->getSize(),
                    'checksum' => $checksum,
                    'status' => Document::STATUS_ACTIVE,
                    'uploaded_by' => auth()->id(),
                ]);
                $imported++;
            } catch (\Throwable $exception) {
                Storage::disk(Document::PRIVATE_DISK)->delete($path);

                throw $exception;
            }
        }

        $this->bulkImportSummary = compact('imported', 'skipped', 'unmatched');
        $this->reset('bulkFiles');

        Notification::make()
            ->success()
            ->title("{$imported} file SK berhasil dicocokkan")
            ->body(count($unmatched).' tidak cocok, '.$skipped.' duplikat dilewati.')
            ->send();
    }

    /** @return array<string, mixed> */
    private function pageData(): array
    {
        $baseDocuments = Document::query()
            ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
            ->where('owner_type', User::class)
            ->where('status', Document::STATUS_ACTIVE);
        $selectedUser = filled($this->selectedUserId)
            ? User::query()->with(['employee.school', 'memberships.school'])->find($this->selectedUserId)
            : null;

        return [
            'totalDocuments' => (clone $baseDocuments)->count(),
            'currentMonthDocuments' => (clone $baseDocuments)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'linkedUsers' => (clone $baseDocuments)->distinct()->count('owner_id'),
            'latestDocument' => (clone $baseDocuments)->with('owner')->latest()->first(),
            'selectedUser' => $selectedUser,
            'editingDocumentId' => $this->editingDocumentId,
            'decreeDate' => $this->decreeDate,
            'userSearch' => $this->userSearch,
            'userResults' => $this->userResults(),
            'showTemplateForm' => $this->showTemplateForm,
            'bulkImportSummary' => $this->bulkImportSummary,
            'templates' => DecreeTemplate::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'templateOptions' => DecreeTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),
            'schoolOptions' => School::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),
            'yearOptions' => Document::query()
                ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
                ->pluck('document_type')
                ->map(fn (string $type): string => $this->documentYear($type))
                ->filter(fn (string $year): bool => preg_match('/^\d{4}$/', $year) === 1)
                ->unique()->sortDesc()->values(),
            'kindOptions' => Document::query()
                ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
                ->whereNotNull('decree_kind')
                ->distinct()->orderBy('decree_kind')->pluck('decree_kind'),
            'documents' => $this->documents(),
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    private function userResults(): \Illuminate\Database\Eloquent\Collection
    {
        $search = trim($this->userSearch);

        if (mb_strlen($search) < 2 || filled($this->selectedUserId)) {
            return User::query()->whereRaw('1 = 0')->get();
        }

        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%")
                    ->orWhereHas('employee', fn (Builder $query): Builder => $query
                        ->whereLike('employee_code', "%{$search}%")
                        ->orWhereLike('nik', "%{$search}%")
                        ->orWhereLike('nip', "%{$search}%")
                        ->orWhereLike('nuptk', "%{$search}%")
                        ->orWhereHas('school', fn (Builder $query): Builder => $query
                            ->whereLike('name', "%{$search}%")))
                    ->orWhereHas('memberships', fn (Builder $query): Builder => $query
                        ->active()
                        ->whereHas('school', fn (Builder $query): Builder => $query
                            ->whereLike('name', "%{$search}%")));
            })
            ->with(['employee.school', 'memberships.school'])
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    private function documents(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $search = trim($this->documentSearch);
        $matchingUserIds = User::query()
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")
                        ->orWhereHas('employee', fn (Builder $query): Builder => $query
                            ->whereLike('employee_code', "%{$search}%")
                            ->orWhereHas('school', fn (Builder $query): Builder => $query
                                ->whereLike('name', "%{$search}%")))
                        ->orWhereHas('memberships', fn (Builder $query): Builder => $query
                            ->active()
                            ->whereHas('school', fn (Builder $query): Builder => $query
                                ->whereLike('name', "%{$search}%")));
                });
            })
            ->select('id');

        $documents = Document::query()
            ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
            ->where('owner_type', User::class)
            ->where('status', Document::STATUS_ACTIVE)
            ->when(filled($this->documentYearFilter), fn (Builder $query): Builder => $query
                ->where('document_type', 'like', self::DOCUMENT_PREFIX.$this->documentYearFilter.'%'))
            ->when(filled($this->documentKindFilter), fn (Builder $query): Builder => $query
                ->where('decree_kind', $this->documentKindFilter))
            ->when(filled($this->documentSchoolFilter), function (Builder $query): void {
                $userIds = User::query()
                    ->whereHas('employee', fn (Builder $query): Builder => $query->where('school_id', $this->documentSchoolFilter))
                    ->orWhereHas('memberships', fn (Builder $query): Builder => $query->active()->where('school_id', $this->documentSchoolFilter))
                    ->select('id');
                $query->whereIn('owner_id', $userIds);
            })
            ->when(filled($search), function (Builder $query) use ($search, $matchingUserIds): void {
                $query->where(function (Builder $query) use ($search, $matchingUserIds): void {
                    $query
                        ->whereLike('original_name', "%{$search}%")
                        ->orWhereLike('document_type', "%{$search}%")
                        ->orWhereIn('owner_id', $matchingUserIds);
                });
            })
            ->with('owner')
            ->latest()
            ->paginate(15);
        $templateNames = DecreeTemplate::query()->pluck('name', 'id');

        $documents->setCollection($documents->getCollection()->map(function (Document $document) use ($templateNames): array {
            /** @var User|null $owner */
            $owner = $document->owner;
            $owner?->loadMissing(['employee.school', 'memberships.school']);
            $school = $owner?->employee?->school
                ?? $owner?->memberships?->firstWhere('school_id', '!=', null)?->school;

            return [
                'id' => $document->getKey(),
                'user' => $owner?->name ?? 'User tidak tersedia',
                'school' => $school?->name ?? '-',
                'year' => $this->documentYear($document->document_type),
                'kind' => $document->decree_kind ?: $templateNames->get($this->documentTemplateId($document->document_type), 'SK Yayasan'),
                'number' => $document->decree_number ?: '-',
                'date' => $document->decree_date?->format('d-m-Y') ?: '-',
                'notes' => $document->notes ?: '-',
                'file' => $document->original_name,
                'uploadedAt' => $document->created_at?->format('d-m-Y H:i'),
                'downloadUrl' => route('documents.download', $document),
            ];
        }));

        return $documents;
    }

    private function resetTemplateForm(): void
    {
        $this->reset(
            'showTemplateForm',
            'editingTemplateId',
            'templateName',
            'decreeTemplateFile',
        );
        $this->templatePaperSize = 'A4';
        $this->templateOrientation = 'portrait';
        $this->templateIsActive = true;
        $this->resetValidation();
    }

    private function documentType(string $year, ?int $templateId = null): string
    {
        return self::DOCUMENT_PREFIX.$year.($templateId ? ':template:'.$templateId : '');
    }

    private function documentYear(string $documentType): string
    {
        return explode(':', $documentType)[1] ?? '-';
    }

    private function documentTemplateId(string $documentType): ?int
    {
        $parts = explode(':', $documentType);

        return ($parts[2] ?? null) === 'template' && isset($parts[3])
            ? (int) $parts[3]
            : null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>  $users
     */
    private function matchUserForFile(string $fileName, \Illuminate\Database\Eloquent\Collection $users): ?User
    {
        $normalizedFileName = $this->normalizeMatchValue(pathinfo($fileName, PATHINFO_FILENAME));
        $matches = [];

        foreach ($users as $user) {
            $employee = $user->employee;
            $identifiers = array_filter([
                $employee?->employee_code,
                $employee?->nik,
                $employee?->nip,
                $employee?->nuptk,
            ]);
            $score = 0;

            foreach ($identifiers as $identifier) {
                $normalizedIdentifier = $this->normalizeMatchValue((string) $identifier);

                if (mb_strlen($normalizedIdentifier) >= 4 && str_contains($normalizedFileName, $normalizedIdentifier)) {
                    $score = max($score, 100 + mb_strlen($normalizedIdentifier));
                }
            }

            $normalizedName = $this->normalizeMatchValue($user->name);

            if (mb_strlen($normalizedName) >= 5 && str_contains($normalizedFileName, $normalizedName)) {
                $score = max($score, 50 + mb_strlen($normalizedName));
            }

            if ($score > 0) {
                $matches[] = ['user' => $user, 'score' => $score];
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        if (isset($matches[1]) && $matches[0]['score'] === $matches[1]['score']) {
            return null;
        }

        return $matches[0]['user'];
    }

    private function normalizeMatchValue(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii($value))) ?? '';
    }
}
