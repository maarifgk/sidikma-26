<?php

namespace App\Filament\Resources\DecreeCorrectionRequests\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\EditRecord;
use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use App\Models\DecreeCorrectionRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class EditDecreeCorrectionRequest extends EditRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = DecreeCorrectionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label($this->record->status === DecreeCorrectionRequest::STATUS_REVISION ? 'Ajukan Kembali' : 'Ajukan Perbaikan')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => Gate::allows('update', $this->record)
                    && ! (auth()->user()?->isAdminInduk() ?? false))
                ->action(function (): void {
                    Gate::authorize('update', $this->record);

                    $this->save(shouldRedirect: false);
                    $this->record->update([
                        'status' => DecreeCorrectionRequest::STATUS_SUBMITTED,
                        'submitted_at' => now(),
                        'admin_notes' => null,
                    ]);
                    Notification::make()
                        ->title('Pengajuan Perbaikan SK baru')
                        ->body($this->record->request_number.' - '.$this->record->school->name)
                        ->warning()
                        ->sendToDatabase(User::role(User::ROLE_ADMIN_INDUK)->get());
                    Notification::make()
                        ->success()
                        ->icon('heroicon-o-check-circle')
                        ->title('Data berhasil diajukan')
                        ->send();
                    $this->redirect($this->getRedirectUrl());
                }),
        ];
    }
}
