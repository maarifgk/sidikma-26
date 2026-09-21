<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ApprovalRequests\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Services\DashboardMetrics;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Gate;

class RecentApprovalsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && Gate::forUser($user)->allows('viewAny', ApprovalRequest::class);
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $table
            ->heading('Approval Terbaru')
            ->query(
                app(DashboardMetrics::class)
                    ->approvals($user)
                    ->with(['approvable', 'submittedBy'])
                    ->latest('updated_at')
                    ->latest('id')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('approvable_label')
                    ->label('Data')
                    ->getStateUsing(fn (ApprovalRequest $record): string => $record->approvableLabel())
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => ApprovalRequest::statusOptions()[$state] ?? $state,
                    ),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since(),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ApprovalRequest $record): string => ApprovalRequestResource::getUrl(
                        'view',
                        ['record' => $record],
                        panel: 'admin',
                    )),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada approval');
    }
}
