<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceStat; // Import your AttendanceStat model
use Carbon\Carbon;

class StudentBarChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Monthly Student Entries';


    /**
     * This widget is only visible to users with the 'Manager' role.
     */
    public static function canView(): bool
    {
        return auth()->user() && !auth()->user()->hasRole('accountant');
    }


    protected function getData(): array
    {
        // Fetch all data from the Sushi model.
        $stats = AttendanceStat::all();

        // Group the statistics by month and year.
        $monthlyStats = $stats->groupBy(function ($stat) {
            return Carbon::parse($stat->date)->format('Y-m');
        });

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
        return 'bar'; // Using a line chart to show trends over time.
    }
}
