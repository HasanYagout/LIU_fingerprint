<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceStat;
use App\Services\AttendanceStatCache;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use HasWidgetShield;
    protected  ?string $pollingInterval = null;
    protected static bool $isLazy = true;

    protected static ?int $sort=1;
    protected function getStats(): array
    {
        sleep(1); // Delay before widget refresh to avoid flicker

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

    protected function getFilteredData(): array
    {
        $filters = $this->filters;

        $startDate = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->endOfMonth()->format('Y-m-d');
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $stats = AttendanceStatCache::get($start, $end);


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
}
