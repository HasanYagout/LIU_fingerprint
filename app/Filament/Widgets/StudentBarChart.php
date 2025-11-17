<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceStat;
use App\Services\AttendanceStatCache;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class StudentBarChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasWidgetShield;
    protected ?string $heading = 'Monthly Student Entries';
    protected static bool $isLazy = true;

    protected static ?int $sort=3;
    protected function getData(): array
    {
        sleep(1); // Delay before widget refresh to avoid flicker

        $filters = $this->filters;
        $startDate = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->endOfMonth()->format('Y-m-d');
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();


        $stats = AttendanceStatCache::get($start, $end);

        $monthlyStats = $stats->groupBy(fn($stat) => Carbon::parse($stat['start'])->format('Y-m'));

        $labels = [];
        $totalEnteredData = [];
        $unpaidUsersData = [];

        // Process each month's aggregated data.
        foreach ($monthlyStats as $month => $data) {
            // Format the month for the chart label (e.g., "Jul 2025").
            $labels[] = Carbon::createFromFormat('Y-m', $month)->format('M Y');

            // Sum the total entered users for the month.
            $totalEnteredData[] = $data->sum('unique_entered_users');

            // Sum the total unpaid users for the month.
            $unpaidUsersData[] = $data->sum('unique_not_paid_users');
        }
        return [
            'datasets' => [
                [
                    'label' => 'Total Entered Users',
                    'data' => $totalEnteredData,
                    'borderColor' => 'hsl(208, 88%, 45%)',       // Vivid blue border
                    'backgroundColor' => 'hsl(208, 88%, 85%)',    // Soft blue fill
                    'fill' => true,                               // Fill under line (if line chart)
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Unpaid Users',
                    'data' => $unpaidUsersData,
                    'borderColor' => 'hsl(348, 83%, 45%)',        // Vivid red border
                    'backgroundColor' => 'hsl(348, 83%, 85%)',     // Soft red fill
                    'fill' => true,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
