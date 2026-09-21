<?php

namespace App\Filament\Resources\DecreeCorrectionRequests\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use App\Models\DecreeCorrectionRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class ViewDecreeCorrectionRequest extends ViewRecord
{
    protected static string $resource = DecreeCorrectionRequestResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('oldFile')->label('Download SK Lama')->url(fn () => route('decree-corrections.download', [$this->record, 'old']))->openUrlInNewTab(),
            Action::make('supportingFile')->label('Download Dokumen Pendukung')->visible(fn () => filled($this->record->supporting_document_path))->url(fn () => route('decree-corrections.download', [$this->record, 'supporting']))->openUrlInNewTab(),
            Action::make('resultFile')->label('Download SK Hasil Perbaikan')->color('success')->visible(fn () => filled($this->record->corrected_decree_path))->url(fn () => route('decree-corrections.download', [$this->record, 'result']))->openUrlInNewTab(),
        ];
        if (! auth()->user()?->isAdminInduk()) {
            return $actions;
        }

        return [...$actions,
            EditAction::make()
                ->label('Edit Data Pengajuan')
                ->icon('heroicon-o-pencil-square')
                ->color('info'),
            Action::make('processCorrection')
                ->label('Proses Perbaikan SK')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->modalHeading('Proses Pengajuan Perbaikan SK')
                ->modalDescription('Periksa permintaan, tentukan status, dan unggah SK hasil jika disetujui.')
                ->modalSubmitActionLabel('Simpan')
                ->visible(fn (): bool => ! in_array($this->record->status, [
                    DecreeCorrectionRequest::STATUS_APPROVED,
                    DecreeCorrectionRequest::STATUS_REJECTED,
                ], true))
                ->fillForm(fn (): array => [
                    'status' => $this->record->status === DecreeCorrectionRequest::STATUS_DRAFT
                        ? DecreeCorrectionRequest::STATUS_PROCESSING
                        : $this->record->status,
                    'admin_notes' => $this->record->admin_notes,
                    'corrected_decree_path' => $this->record->corrected_decree_path,
                ])
                ->schema([
                    Select::make('status')
                        ->label('STATUS PENGAJUAN')
                        ->options([
                            DecreeCorrectionRequest::STATUS_SUBMITTED => 'Diajukan',
                            DecreeCorrectionRequest::STATUS_PROCESSING => 'Sedang Diproses',
                            DecreeCorrectionRequest::STATUS_REVISION => 'Minta Revisi',
                            DecreeCorrectionRequest::STATUS_APPROVED => 'Proses Selesai',
                            DecreeCorrectionRequest::STATUS_REJECTED => 'Ditolak',
                        ])
                        ->live()
                        ->required()
                        ->native(false),
                    Textarea::make('admin_notes')
                        ->label('CATATAN ADMIN INDUK')
                        ->rows(4)
                        ->required(fn (Get $get): bool => in_array($get('status'), [
                            DecreeCorrectionRequest::STATUS_REVISION,
                            DecreeCorrectionRequest::STATUS_REJECTED,
                        ], true)),
                    FileUpload::make('corrected_decree_path')
                        ->label('UPLOAD SK HASIL PERBAIKAN (PDF)')
                        ->disk('documents')
                        ->directory('decree-corrections/results')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(1024000)
                        ->visible(fn (Get $get): bool => $get('status') === DecreeCorrectionRequest::STATUS_APPROVED)
                        ->required(fn (Get $get): bool => $get('status') === DecreeCorrectionRequest::STATUS_APPROVED),
                ])
                ->action(function (array $data): void {
                    $status = $data['status'];
                    $updates = [
                        'admin_notes' => $data['admin_notes'] ?? null,
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                    ];

                    if ($status === DecreeCorrectionRequest::STATUS_APPROVED) {
                        $updates['corrected_decree_path'] = $data['corrected_decree_path'];
                        $updates['approved_by'] = auth()->id();
                        $updates['approved_at'] = now();
                    }

                    $this->applyStatusChange($status, $updates);
                }),
        ];
    }

    private function applyStatusChange(string $status, array $data = []): void
    {
        $this->record->update(['status' => $status, ...$data]);
        Notification::make()->title('Status Perbaikan SK: '.(DecreeCorrectionRequest::statusOptions()[$status] ?? $status))->sendToDatabase($this->record->submitter);
        Notification::make()->title('Status berhasil diperbarui')->success()->send();
        $this->refreshFormData(['status', 'admin_notes', 'processed_by', 'processed_at', 'approved_by', 'approved_at', 'corrected_decree_path']);
    }
}
