<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceStat;
use App\Services\AttendanceStatCache;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class StudentChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasWidgetShield;
    protected ?string $heading = 'Student Entry Status';
    protected static bool $isLazy = true;

    protected static ?int $sort=4;
    public ?array $filterData;
    protected function getData(): array
    {
        sleep(1); // Delay before widget refresh to avoid flicker

        $filters = $this->filters;

        $startDate = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->endOfMonth()->format('Y-m-d');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();


        $stats = AttendanceStatCache::get($start, $end);

        // ✅ Now sums work
        $totalUnpaidUsers = $stats->sum('unique_not_paid_users');
        $totalEnteredUsers = $stats->sum('unique_entered_users');
        return [
            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => [
                        $totalEnteredUsers,
                        $totalUnpaidUsers,
                    ],

                    'backgroundColor' => [
                        'hsl(133, 88%, 90%)', // Light blue
                        'hsl(348, 83%, 90%)', // Light red
                    ],

                    // Vivid borders
                    'borderColor' => [
                        'hsl(133, 88%, 60%)',
                        'hsl(348, 83%, 60%)',
                    ],
                    'borderWidth' => 2,
                ]
            ],
            'labels' => [
                'Entered Users',
                'Unpaid Users',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
