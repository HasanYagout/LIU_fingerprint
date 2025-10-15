<?php
namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Sushi\Sushi;

class AttendanceStat extends Model
{
    use Sushi;

    protected $casts = [
        'start' => 'date',
        'end' => 'date',
    ];
    protected static $startDate;
    protected static $endDate;

    public static function setDateRange($start, $end)
    {
        static::$startDate = $start;
        static::$endDate = $end;
        static::clearBootedModels(); // Clear cached data
    }
    public function getRows(): array
    {
        $start = static::$startDate ?? now()->subDays(15)->format('Ymd');
        $end = static::$endDate ?? now()->addDays(15)->format('Ymd');

        $cacheKey = "attendance_stats_{$start}_{$end}";

        return cache()->remember($cacheKey, now()->addMinutes(10), function () use ($start, $end) {
            try {
                $response = Http::withBasicAuth(config('services.api.username'), config('services.api.password'))
                    ->timeout(10)
                    ->post('http://192.168.8.118:2000/api/v1/attendance-stats', [
                        'startDate' => $start,
                        'endDate' => $end,
                    ]);

                if ($response->successful() && $response->json('success')) {
                    $stats = $response->json('dailyStats') ?? [];

                    return collect($stats)->map(function ($stat) {
                        $date = \Carbon\Carbon::createFromFormat('Ymd', $stat['date'])->toDateString();

                        return [
                            'id' => $stat['date'],
                            'title' => "Entries: {$stat['uniqueEntries']} (Unpaid: {$stat['uniqueNotPaidUsers']})",
                            'start' => $date,
                            'end' => $date,
                            'unique_entries' => $stat['uniqueEntries'],
                            'unique_entered_users' => $stat['uniqueEnteredUsers'],
                            'unique_not_paid_users' => $stat['uniqueNotPaidUsers'],
                        ];
                    })->toArray();
                }
            } catch (\Exception $e) {
                \Log::error('Attendance stats API request exception', ['error' => $e->getMessage()]);
            }

            return [];
        });
    }

}
