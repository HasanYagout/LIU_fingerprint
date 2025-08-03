<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;


class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        // Get the filtered data
        $data = $this->getFilteredData();

        if (auth()->user() && !auth()->user()->hasRole('accountant')) {
            // If the user is a Manager, get the filtered data.
            $data = $this->getFilteredData();

            // Return the array of Stat objects.
            return [
                Stat::make('Not Paid Students', $data['statistics']['totalNotPaidUsers'] ?? 'N/A')
                    ->description('Students who entered but didn\'t pay')
                    ->color('danger'),
                Stat::make('Paid Students', $data['statistics']['totalEnteredUsers'] ?? 'N/A')
                    ->description('Total students who entered')
                    ->color('info'),
                Stat::make('All Students', $data['statistics']['totalEnteredUsers']+$data['statistics']['totalNotPaidUsers'] ?? 'N/A')
                    ->description('Total students')
                    ->color('primary'),
            ];
        }

        // If the user is not a 'Manager' or is not logged in,
        // return an empty array. This will hide the stats.
        return [];
    }


    protected function getFilteredData(): array
    {
        // Get current filters
        $filters = $this->filters;
        $startDate = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->endOfMonth()->format('Y-m-d');

        // Convert dates to match your data format (Ymd)
        $startDateFormatted = Carbon::parse($startDate)->format('Ymd');
        $endDateFormatted = Carbon::parse($endDate)->format('Ymd');

        try {
            // Make API request using Laravel's HTTP client
            $response = Http::withBasicAuth(config('services.api.username'), config('services.api.password'))
                ->post('http://172.170.17.5:2001/api/v1/attendance-stats', [
                    'startDate' => $startDateFormatted,
                    'endDate' => $endDateFormatted,
                ]);

            // Get the JSON response (automatically decoded)
            $apiData = $response->json();
            $dailyStats = $apiData['dailyStats'] ?? [];

        } catch (\Exception $e) {
            // Log error if needed
            logger()->error('Failed to fetch attendance stats: ' . $e->getMessage());
            $dailyStats = [];
        }

        // Filter dailyStats based on the selected date range
        $filteredDailyStats = array_filter($dailyStats, function($day) use ($startDateFormatted, $endDateFormatted) {
            return $day['date'] >= $startDateFormatted && $day['date'] <= $endDateFormatted;
        });

        // Re-index array after filtering
        $filteredDailyStats = array_values($filteredDailyStats);

        // Calculate statistics based on filtered data
        $totalDays = count($filteredDailyStats);
        $totalUniqueEntries = array_sum(array_column($filteredDailyStats, 'uniqueEntries'));
        $totalNotPaidUsers = array_sum(array_column($filteredDailyStats, 'uniqueNotPaidUsers'));
        $totalEnteredUsers = array_sum(array_column($filteredDailyStats, 'uniqueEnteredUsers'));
        $averageDailyEntries = $totalDays > 0 ? round($totalUniqueEntries / $totalDays, 2) : 0;

        return [
            "dateRange" => [
                "startDate" => $startDateFormatted,
                "endDate" => $endDateFormatted
            ],
            "statistics" => [
                "totalDays" => $totalDays,
                "totalUniqueEntries" => $totalUniqueEntries,
                "averageDailyEntries" => $averageDailyEntries,
                "totalNotPaidUsers" => $totalNotPaidUsers,
                "totalEnteredUsers" => $totalEnteredUsers
            ],
            "dailyStats" => $filteredDailyStats
        ];
    }
    protected function getDateRangeDescription(): string
    {
        $filters = $this->filters;
        $startDate = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->endOfMonth()->format('Y-m-d');

        $start = Carbon::parse($startDate)->format('M j, Y');
        $end = Carbon::parse($endDate)->format('M j, Y');

        return "From {$start} to {$end}";
    }
}
