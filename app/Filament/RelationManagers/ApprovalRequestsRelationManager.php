<?php

namespace App\Filament\RelationManagers;

use App\Filament\Resources\ApprovalRequests\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Services\ApprovalWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class ApprovalRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalRequests';

    protected static ?string $title = 'Riwayat Approval';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withoutGlobalScopes([SoftDeletingScope::class]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No.')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => ApprovalRequest::statusOptions()[$state] ?? $state,
                    )
                    ->sortable(),
                TextColumn::make('submittedBy.name')
                    ->label('Pengaju')
                    ->placeholder('Belum diajukan'),
                TextColumn::make('submitted_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('decidedBy.name')
                    ->label('Pengambil Keputusan')
                    ->placeholder('-'),
                TextColumn::make('decided_at')
                    ->label('Tanggal Keputusan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ApprovalRequest::statusOptions()),
                TrashedFilter::make(),
            ])
            ->headerActions([
                Action::make('submitApproval')
                    ->label('Ajukan Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan Pengajuan')
                            ->rows(4)
                            ->maxLength(5000),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (): bool => Gate::allows('create', ApprovalRequest::class)
                        && ! $this->hasOpenRequest())
                    ->action(function (array $data, Action $action): void {
                        $user = auth()->user();
                        abort_unless($user instanceof User, 403);

                        app(ApprovalWorkflow::class)->createAndSubmit(
                            $this->getOwnerRecord(),
                            $user,
                            $data['notes'] ?? null,
                        );

                        $action->successNotificationTitle('Pengajuan berhasil dikirim')->success();
                    }),
            ])
            ->recordActions([
                Action::make('viewApproval')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ApprovalRequest $record): string => ApprovalRequestResource::getUrl(
                        'view',
                        ['record' => $record],
                        panel: 'admin',
                    )),
            ])
            ->emptyStateHeading('Belum ada riwayat approval')
            ->emptyStateDescription('Klik Ajukan Approval untuk memulai proses persetujuan.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    private function hasOpenRequest(): bool
    {
        return $this->getOwnerRecord()
            ->approvalRequests()
            ->whereIn('status', ApprovalRequest::openStatuses())
            ->exists();
    }
}
