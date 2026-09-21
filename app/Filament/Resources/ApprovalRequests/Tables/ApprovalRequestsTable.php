<?php

namespace App\Filament\Resources\ApprovalRequests\Tables;

use App\Models\ApprovalRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ApprovalRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'approvable',
                'submittedBy',
                'verifiedBy',
                'decidedBy',
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No.')
                    ->sortable(),
                TextColumn::make('approvable_type')
                    ->label('Jenis Data')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => ApprovalRequest::approvableTypeOptions()[$state] ?? $state,
                    )
                    ->sortable(),
                TextColumn::make('approvable_label')
                    ->label('Data yang Diajukan')
                    ->getStateUsing(fn (ApprovalRequest $record): string => $record->approvableLabel())
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => ApprovalRequest::statusOptions()[$state] ?? $state,
                    )
                    ->color(fn (string $state): string => match ($state) {
                        ApprovalRequest::STATUS_DRAFT => 'gray',
                        ApprovalRequest::STATUS_SUBMITTED => 'warning',
                        ApprovalRequest::STATUS_VERIFIED => 'info',
                        ApprovalRequest::STATUS_APPROVED => 'success',
                        ApprovalRequest::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('submittedBy.name')
                    ->label('Pengaju')
                    ->placeholder('Belum diajukan')
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('verifiedBy.name')
                    ->label('Verifikator')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('decidedBy.name')
                    ->label('Pengambil Keputusan')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ApprovalRequest::statusOptions()),
                SelectFilter::make('approvable_type')
                    ->label('Jenis Data')
                    ->options(ApprovalRequest::approvableTypeOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail'),
                self::submitAction(),
                self::verifyAction(),
                self::approveAction(),
                self::rejectAction(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->emptyStateHeading('Belum ada pengajuan approval')
            ->emptyStateDescription('Pengajuan dari dokumen atau data induk akan tampil di halaman ini.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    private static function submitAction(): Action
    {
        return Action::make('submit')
            ->label('Ajukan')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->schema([
                Textarea::make('notes')
                    ->label('Catatan Pengajuan')
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->requiresConfirmation()
            ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalRequest::STATUS_DRAFT
                && Gate::allows('submit', $record))
            ->action(function (ApprovalRequest $record, array $data, Action $action): void {
                Gate::authorize('submit', $record);

                $user = auth()->user();
                abort_unless($user instanceof User, 403);

                $record->submit($user, $data['notes'] ?? null);
                $action->successNotificationTitle('Pengajuan berhasil dikirim')->success();
            });
    }

    private static function verifyAction(): Action
    {
        return Action::make('verify')
            ->label('Verifikasi')
            ->icon('heroicon-o-magnifying-glass-circle')
            ->color('info')
            ->schema([
                Textarea::make('notes')
                    ->label('Catatan Verifikasi')
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->requiresConfirmation()
            ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalRequest::STATUS_SUBMITTED
                && Gate::allows('verify', $record))
            ->action(function (ApprovalRequest $record, array $data, Action $action): void {
                Gate::authorize('verify', $record);

                $user = auth()->user();
                abort_unless($user instanceof User, 403);

                $record->verify($user, $data['notes'] ?? null);
                $action->successNotificationTitle('Pengajuan berhasil diverifikasi')->success();
            });
    }

    private static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Setujui')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->schema([
                Textarea::make('notes')
                    ->label('Catatan Persetujuan')
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->requiresConfirmation()
            ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalRequest::STATUS_VERIFIED
                && Gate::allows('approve', $record))
            ->action(function (ApprovalRequest $record, array $data, Action $action): void {
                Gate::authorize('approve', $record);

                $user = auth()->user();
                abort_unless($user instanceof User, 403);

                $record->approve($user, $data['notes'] ?? null);
                $action->successNotificationTitle('Pengajuan berhasil disetujui')->success();
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Tolak')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->schema([
                Textarea::make('notes')
                    ->label('Alasan Penolakan')
                    ->rows(4)
                    ->required()
                    ->maxLength(5000),
            ])
            ->requiresConfirmation()
            ->visible(fn (ApprovalRequest $record): bool => in_array($record->status, [
                ApprovalRequest::STATUS_SUBMITTED,
                ApprovalRequest::STATUS_VERIFIED,
            ], true) && Gate::allows('reject', $record))
            ->action(function (ApprovalRequest $record, array $data, Action $action): void {
                Gate::authorize('reject', $record);

                $user = auth()->user();
                abort_unless($user instanceof User, 403);

                $record->reject($user, $data['notes']);
                $action->successNotificationTitle('Pengajuan ditolak')->success();
            });
    }
}
