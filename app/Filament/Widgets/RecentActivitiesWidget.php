<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Activities\ActivityResource;
use App\Models\Activity;
use App\Models\User;
use App\Services\DashboardMetrics;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Gate;

class RecentActivitiesWidget extends TableWidget
{
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && Gate::forUser($user)->allows('viewAny', Activity::class);
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $table
            ->heading('Aktivitas Terbaru')
            ->query(
                app(DashboardMetrics::class)
                    ->activities($user)
                    ->with(['subject', 'causer'])
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('description')
                    ->label('Aktivitas')
                    ->wrap(),
                TextColumn::make('causer_label')
                    ->label('Pelaku')
                    ->getStateUsing(fn (Activity $record): string => $record->causerLabel()),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since(),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Activity $record): string => ActivityResource::getUrl(
                        'view',
                        ['record' => $record],
                        panel: 'admin',
                    )),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada aktivitas');
    }
}
