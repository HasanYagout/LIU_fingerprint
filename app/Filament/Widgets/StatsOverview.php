<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceStat;
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

    protected static ?int $sort=1;

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
        $filters = $this->filters;
        $startDate = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->endOfMonth()->format('Y-m-d');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Fetch and filter Sushi demo data
        $stats = AttendanceStat::query()
            ->get();
        $totalDays = $stats->count();
        $totalUniqueEntries = $stats->sum('unique_entered_users'); // or `unique_entries` if using separate column
        $totalNotPaidUsers = $stats->sum('unique_not_paid_users');
        $totalEnteredUsers = $stats->sum('unique_entered_users');
        $averageDailyEntries = $totalDays > 0 ? round($totalEnteredUsers / $totalDays, 2) : 0;

        return [
            'dateRange' => [
                'startDate' => $start->format('Ymd'),
                'endDate' => $end->format('Ymd'),
            ],
            'statistics' => [
                'totalDays' => $totalDays,
                'totalUniqueEntries' => $totalUniqueEntries,
                'averageDailyEntries' => $averageDailyEntries,
                'totalNotPaidUsers' => $totalNotPaidUsers,
                'totalEnteredUsers' => $totalEnteredUsers,
            ],
            'dailyStats' => $stats->toArray(),
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
