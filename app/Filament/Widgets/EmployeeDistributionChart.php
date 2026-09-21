<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\User;
use App\Services\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class EmployeeDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Komposisi Guru dan Pegawai';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $counts = app(DashboardMetrics::class)
            ->employees($user)
            ->selectRaw('employee_type, count(*) as total')
            ->groupBy('employee_type')
            ->pluck('total', 'employee_type');

        return [
            'datasets' => [[
                'label' => 'Jumlah',
                'data' => [
                    (int) $counts->get(Employee::TYPE_GURU, 0),
                    (int) $counts->get(Employee::TYPE_PEGAWAI, 0),
                ],
            ]],
            'labels' => ['Guru', 'Pegawai'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
