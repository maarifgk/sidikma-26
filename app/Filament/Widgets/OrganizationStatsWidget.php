<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Data';

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $metrics = app(DashboardMetrics::class);
        $scope = $metrics->scopeLabel($user);

        return [
            Stat::make('Total Sekolah/Madrasah', $metrics->schools($user)->count())
                ->description($scope.' · MI, MTs, dan SMP')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->icon('heroicon-o-building-office-2'),
            Stat::make('Guru dan Pegawai', $metrics->employees($user)->count())
                ->description(
                    $user->hasRole(User::ROLE_GURU_PEGAWAI)
                        ? 'Profil guru/pegawai Anda'
                        : 'Sesuai sekolah yang dapat diakses',
                )
                ->descriptionIcon('heroicon-m-identification')
                ->icon('heroicon-o-identification'),
            Stat::make(
                'Pengguna Aktif',
                $metrics->users($user)->where('is_active', true)->count(),
            )
                ->description('Akun aktif dalam cakupan')
                ->descriptionIcon('heroicon-m-user-group')
                ->icon('heroicon-o-user-group'),
        ];
    }
}
