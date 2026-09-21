<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class SchoolLevelChart extends ChartWidget
{
    protected ?string $heading = 'Sebaran Jenjang Sekolah';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $counts = app(DashboardMetrics::class)
            ->schools($user)
            ->selectRaw('school_level, count(*) as total')
            ->groupBy('school_level')
            ->pluck('total', 'school_level');

        return [
            'datasets' => [[
                'label' => 'Jumlah Sekolah',
                'data' => [
                    (int) $counts->get('MI', 0),
                    (int) $counts->get('MTs', 0),
                    (int) $counts->get('SMP', 0),
                ],
            ]],
            'labels' => ['MI', 'MTs', 'SMP'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
