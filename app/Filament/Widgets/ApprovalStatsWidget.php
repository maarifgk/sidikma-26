<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ApprovalRequests\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Services\DashboardMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class ApprovalStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Status Approval';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && Gate::forUser($user)->allows('viewAny', ApprovalRequest::class);
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $metrics = app(DashboardMetrics::class);
        $counts = $metrics
            ->approvals($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $url = ApprovalRequestResource::getUrl('index', panel: 'admin');

        return [
            Stat::make('Menunggu Verifikasi', (int) $counts->get(ApprovalRequest::STATUS_SUBMITTED, 0))
                ->description('Pengajuan baru')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->url($url),
            Stat::make('Menunggu Persetujuan', (int) $counts->get(ApprovalRequest::STATUS_VERIFIED, 0))
                ->description('Sudah diverifikasi')
                ->descriptionIcon('heroicon-m-magnifying-glass-circle')
                ->url($url),
            Stat::make('Disetujui', (int) $counts->get(ApprovalRequest::STATUS_APPROVED, 0))
                ->description('Approval selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->url($url),
            Stat::make('Ditolak', (int) $counts->get(ApprovalRequest::STATUS_REJECTED, 0))
                ->description('Memerlukan perbaikan')
                ->descriptionIcon('heroicon-m-x-circle')
                ->url($url),
        ];
    }
}
