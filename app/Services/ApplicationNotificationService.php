<?php

namespace App\Services;

use App\Filament\Resources\ApprovalRequests\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApplicationNotificationService
{
    public function notifyApprovalStatus(ApprovalRequest $request, User $actor): void
    {
        if ($request->status === ApprovalRequest::STATUS_SUBMITTED) {
            $recipients = $this->activeAdminIndukUsers();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::make()
                ->title('Pengajuan approval baru')
                ->body("{$actor->name} mengajukan {$request->approvableLabel()} untuk diperiksa.")
                ->warning()
                ->icon('heroicon-o-paper-airplane')
                ->actions([$this->approvalAction($request)])
                ->sendToDatabase($recipients, isEventDispatched: true);

            return;
        }

        if (! in_array($request->status, [
            ApprovalRequest::STATUS_APPROVED,
            ApprovalRequest::STATUS_REJECTED,
        ], true)) {
            return;
        }

        $recipient = $request->submittedBy;

        if (! $recipient instanceof User) {
            return;
        }

        $approved = $request->status === ApprovalRequest::STATUS_APPROVED;

        Notification::make()
            ->title($approved ? 'Pengajuan disetujui' : 'Pengajuan ditolak')
            ->body($approved
                ? "Pengajuan {$request->approvableLabel()} telah disetujui oleh {$actor->name}."
                : "Pengajuan {$request->approvableLabel()} ditolak. {$request->decision_notes}")
            ->icon($approved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
            ->status($approved ? 'success' : 'danger')
            ->actions([$this->approvalAction($request)])
            ->sendToDatabase($recipient, isEventDispatched: true);
    }

    public function notifyIncompleteDocuments(Model $owner, User $actor, string $notes): int
    {
        $recipients = $this->recipientsForOwner($owner);

        if ($recipients->isEmpty()) {
            $recipients = $this->activeAdminIndukUsers();
        }

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::make()
            ->title('Dokumen belum lengkap')
            ->body("{$this->ownerLabel($owner)}: {$notes} (dikirim oleh {$actor->name})")
            ->warning()
            ->icon('heroicon-o-document-minus')
            ->sendToDatabase($recipients, isEventDispatched: true);

        return $recipients->count();
    }

    /** @param array<int, string> $changedFields */
    public function notifyImportantAccountChange(
        User $target,
        array $changedFields,
        ?User $actor = null,
    ): void {
        if ($changedFields === []) {
            return;
        }

        $labels = collect($changedFields)
            ->map(fn (string $field): string => match ($field) {
                'name' => 'nama',
                'email' => 'email',
                'password' => 'password',
                'is_active' => 'status aktif',
                'roles' => 'role',
                'permissions' => 'permission',
                default => $field,
            })
            ->unique()
            ->implode(', ');

        $actorLabel = $actor?->name ?? 'Sistem';

        Notification::make()
            ->title('Perubahan akun penting')
            ->body("Perubahan pada {$labels} akun Anda dilakukan oleh {$actorLabel}.")
            ->info()
            ->icon('heroicon-o-shield-exclamation')
            ->sendToDatabase($target, isEventDispatched: true);
    }

    private function approvalAction(ApprovalRequest $request): Action
    {
        return Action::make('viewApproval')
            ->label('Lihat Approval')
            ->url(ApprovalRequestResource::getUrl(
                'view',
                ['record' => $request],
                panel: 'admin',
            ))
            ->markAsRead();
    }

    /** @return Collection<int, User> */
    private function activeAdminIndukUsers(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query
                ->where('name', User::ROLE_ADMIN_INDUK)
                ->where('guard_name', 'web'))
            ->where('is_active', true)
            ->get();
    }

    /** @return Collection<int, User> */
    private function recipientsForOwner(Model $owner): Collection
    {
        if ($owner instanceof Document && $owner->owner instanceof Model) {
            return $this->recipientsForOwner($owner->owner);
        }

        if ($owner instanceof Employee) {
            return User::query()
                ->whereKey($owner->user_id)
                ->where('is_active', true)
                ->get();
        }

        if ($owner instanceof School) {
            return Membership::query()
                ->active()
                ->where('school_id', $owner->getKey())
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter(fn (?User $user): bool => $user?->is_active === true)
                ->unique('id')
                ->values();
        }

        if ($owner instanceof Foundation) {
            return Membership::query()
                ->active()
                ->where('foundation_id', $owner->getKey())
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter(fn (?User $user): bool => $user?->is_active === true)
                ->unique('id')
                ->values();
        }

        return collect();
    }

    private function ownerLabel(Model $owner): string
    {
        return match (true) {
            $owner instanceof Document => "Dokumen {$owner->original_name}",
            $owner instanceof Employee => "Guru/Pegawai {$owner->name}",
            $owner instanceof School => "Sekolah/Madrasah {$owner->name}",
            $owner instanceof Foundation => "Yayasan {$owner->name}",
            default => class_basename($owner).' #'.$owner->getKey(),
        };
    }
}
