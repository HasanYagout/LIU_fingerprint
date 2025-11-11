<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
    public function getStatistics(Request $request)
    {




            $startDate = Carbon::createFromFormat('Ymd', $validated['startDate']);
            $endDate = Carbon::createFromFormat('Ymd', $validated['endDate']);

            $statistics = $this->generateStatistics($startDate, $endDate);

            return [
                'success' => true,
                'message' => 'Attendance statistics retrieved successfully',
                'dateRange' => [
                    'startDate' => $validated['startDate'],
                    'endDate' => $validated['endDate'],
                ],
                'statistics' => $statistics['summary'],
                'dailyStats' => $statistics['daily'],
                'timestamp' => now()->toIso8601String(),
            ];

    }

    protected function generateStatistics(Carbon $startDate, Carbon $endDate): array
    {
        // Replace with your actual database queries
        $dailyStats = [];
        $totalDays = 0;
        $totalEntries = 0;

        foreach ($startDate->toPeriod($endDate) as $date) {
            if (rand(0, 3) > 1) {
                $entries = rand(3, 8);
                $dailyStats[] = [
                    'date' => $date->format('Ymd'),
                    'uniqueEntries' => $entries,
                    'uniqueEnteredUsers' => $entries,
                    'uniqueNotPaidUsers' => rand(1, $entries),
                ];
                $totalDays++;
                $totalEntries += $entries;
            }
        }

        return [
            'summary' => [
                'totalDays' => $totalDays,
                'totalUniqueEntries' => $totalEntries,
                'averageDailyEntries' => $totalDays > 0 ? round($totalEntries / $totalDays, 2) : 0,
            ],
            'daily' => $dailyStats,
        ];
    }
}
