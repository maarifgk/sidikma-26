<?php

namespace App\Filament\Resources\ProposalRequests\Tables;

use App\Models\ProposalRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProposalRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('school_name')
                    ->label('Asal Madrasah')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('proposal_type')
                    ->label('Jenis Perm. Bantuan/Proposal')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('request_file_action')
                    ->label('File')
                    ->state(fn (ProposalRequest $record): string => Storage::disk(ProposalRequest::DISK)->exists($record->request_file_path) ? 'Lihat' : 'Belum tersedia')
                    ->badge()
                    ->color(fn (ProposalRequest $record): string => Storage::disk(ProposalRequest::DISK)->exists($record->request_file_path) ? 'primary' : 'gray')
                    ->alignCenter()
                    ->url(fn (ProposalRequest $record): ?string => Storage::disk(ProposalRequest::DISK)->exists($record->request_file_path)
                        ? Storage::disk(ProposalRequest::DISK)->temporaryUrl($record->request_file_path, now()->addMinutes(5)) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('requested_amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn (mixed $state): string => 'Rp. '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('process_status')
                    ->label('Ket. Proses')
                    ->formatStateUsing(
                        fn (string $state): string => ProposalRequest::statusOptions()[$state] ?? $state,
                    )
                    ->sortable()
                    ->wrap(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('approval_file_action')
                    ->label('File Approve')
                    ->state(fn (ProposalRequest $record): string => filled($record->approval_file_path) && Storage::disk(ProposalRequest::DISK)->exists($record->approval_file_path) ? 'Lihat' : '-')
                    ->badge(fn (ProposalRequest $record): bool => filled($record->approval_file_path) && Storage::disk(ProposalRequest::DISK)->exists($record->approval_file_path))
                    ->color('primary')
                    ->alignCenter()
                    ->url(fn (ProposalRequest $record): ?string => filled($record->approval_file_path) && Storage::disk(ProposalRequest::DISK)->exists($record->approval_file_path)
                        ? Storage::disk(ProposalRequest::DISK)->temporaryUrl($record->approval_file_path, now()->addMinutes(5))
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('approved_amount')
                    ->label('Nominal Diterima')
                    ->placeholder('-')
                    ->formatStateUsing(fn (mixed $state): string => filled($state)
                        ? 'Rp. '.number_format((float) $state, 0, ',', '.')
                        : '-')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('process_status')
                    ->label('Status Proses')
                    ->options(ProposalRequest::statusOptions()),
            ])
            ->recordActions([
                self::processAction(),
                self::rejectAction(),
                DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('warning'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada pengajuan proposal')
            ->emptyStateDescription('Klik Ajukan untuk menambahkan pengajuan proposal bantuan.')
            ->emptyStateIcon('heroicon-o-document-currency-dollar');
    }

    private static function processAction(): Action
    {
        return Action::make('process')
            ->label('Proses')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading('Proses pengajuan proposal')
            ->modalDescription('Unggah file persetujuan dan masukkan nominal bantuan yang diterima.')
            ->schema([
                FileUpload::make('approval_file_path')
                    ->label('FILE PERSETUJUAN (PDF)')
                    ->disk(ProposalRequest::DISK)
                    ->visibility('private')
                    ->directory('approval-files')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024000)
                    ->required(),
                TextInput::make('approved_amount')
                    ->label('NOMINAL DITERIMA')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Textarea::make('notes')
                    ->label('CATATAN')
                    ->rows(3)
                    ->maxLength(5000),
            ])
            ->visible(fn (ProposalRequest $record): bool => self::canReview()
                && $record->process_status === ProposalRequest::STATUS_SUBMITTED)
            ->action(function (ProposalRequest $record, array $data, Action $action): void {
                $user = auth()->user();
                abort_unless($user instanceof User && $user->isAdminInduk(), 403);

                DB::transaction(function () use ($record, $data, $user): void {
                    $request = ProposalRequest::query()
                        ->lockForUpdate()
                        ->findOrFail($record->getKey());

                    if ($request->process_status !== ProposalRequest::STATUS_SUBMITTED) {
                        return;
                    }

                    $request->update([
                        'approval_file_path' => $data['approval_file_path'],
                        'approved_amount' => $data['approved_amount'],
                        'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
                        'process_status' => ProposalRequest::STATUS_COMPLETED,
                        'processed_by' => $user->getKey(),
                        'processed_at' => now(),
                    ]);
                });

                $action->successNotificationTitle('Pengajuan proposal berhasil diproses')->success();
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Tolak')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Tolak pengajuan proposal?')
            ->schema([
                Textarea::make('notes')
                    ->label('ALASAN PENOLAKAN')
                    ->required()
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->visible(fn (ProposalRequest $record): bool => self::canReview()
                && $record->process_status === ProposalRequest::STATUS_SUBMITTED)
            ->action(function (ProposalRequest $record, array $data, Action $action): void {
                $user = auth()->user();
                abort_unless($user instanceof User && $user->isAdminInduk(), 403);

                DB::transaction(function () use ($record, $data, $user): void {
                    $request = ProposalRequest::query()
                        ->lockForUpdate()
                        ->findOrFail($record->getKey());

                    if ($request->process_status !== ProposalRequest::STATUS_SUBMITTED) {
                        return;
                    }

                    $request->update([
                        'notes' => $data['notes'],
                        'process_status' => ProposalRequest::STATUS_REJECTED,
                        'processed_by' => $user->getKey(),
                        'processed_at' => now(),
                    ]);
                });

                $action->successNotificationTitle('Pengajuan proposal ditolak')->success();
            });
    }

    private static function canReview(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdminInduk();
    }
}
