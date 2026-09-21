<?php

namespace App\Filament\App\Pages;

use App\Models\Complaint;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MyComplaints extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Pengaduan Saya';

    protected static ?string $slug = 'pengaduan-saya';

    protected string $view = 'filament.app.pages.my-complaints';

    protected static bool $shouldRegisterNavigation = false;

    public string $category = '';

    public string $subject = '';

    public string $description = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public ?int $selectedComplaintId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_GURU_PEGAWAI) ?? false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function submitComplaint(): void
    {
        $data = $this->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(Complaint::categoryOptions()))],
            'subject' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $user = auth()->user();
        abort_unless($user instanceof User && $user->can('create', Complaint::class), 403);

        $employee = $user->employee()->first();
        $storedAttachments = collect($this->attachments)->map(function (TemporaryUploadedFile $file): array {
            $path = $file->store('attachments/'.now()->format('Y/m'), Complaint::DISK);

            return [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        })->all();

        unset($data['attachments']);
        $complaint = Complaint::query()->create($data + [
            'submitted_by' => $user->getKey(),
            'school_id' => $employee?->school_id ?? $user->accessibleSchoolIds()->first(),
            'attachments' => $storedAttachments,
        ]);

        $this->reset('category', 'subject', 'description', 'attachments');
        $this->selectedComplaintId = $complaint->getKey();

        Notification::make()
            ->title('Pengaduan berhasil dikirim secara privat')
            ->body("Nomor pengaduan: {$complaint->reference_number}")
            ->success()
            ->send();
    }

    public function selectComplaint(int $complaintId): void
    {
        $complaint = Complaint::query()->findOrFail($complaintId);
        $this->authorize('view', $complaint);
        $this->selectedComplaintId = $complaint->getKey();
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $complaints = Complaint::query()
            ->where('submitted_by', $user?->getKey())
            ->latest()
            ->get();

        $selectedComplaint = $this->selectedComplaintId
            ? $complaints->firstWhere('id', $this->selectedComplaintId)
            : null;

        return [
            'user' => $user,
            'employee' => $user?->employee()->with('school')->first(),
            'complaints' => $complaints,
            'selectedComplaint' => $selectedComplaint,
            'categories' => Complaint::categoryOptions(),
            'statuses' => Complaint::statusOptions(),
        ];
    }
}
