<?php
namespace App\Models;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
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

        static::$startDate = Carbon::parse($start)->startOfDay();
        static::$endDate = Carbon::parse($end)->endOfDay();
        static::clearBootedModels(); // Reset cached Sushi data
    }
    public function getRows(): array
    {
        $start = static::$startDate ?? now()->startOfDay();
        $end = static::$endDate ?? now()->endOfDay();

        $startFormatted = $start->format('Ymd');
        $endFormatted = $end->format('Ymd');

        try {
            $response = Http::withBasicAuth(
                config('services.api.username'),
                config('services.api.password')
            )
                ->timeout(3)        // ⏳ hard timeout prevents UI freeze
                ->connectTimeout(1) // ⚡ fail fast if API is down
                ->post('http://192.168.1.102:2001/api/v1/attendance-stats', [
                    'startDate' => $startFormatted,
                    'endDate'   => $endFormatted,
                ]);

            // API returned non-success → show error
            if (!$response->successful() || !$response->json('success')) {
                return [
                    [
                        'id' => 'error',
                        'title' => '⚠ API Error (Failed Response)',
                        'start' => now()->toDateString(),
                        'end' => now()->toDateString(),
                        'unique_entries' => 0,
                        'unique_entered_users' => 0,
                        'unique_not_paid_users' => 0,
                    ]
                ];
            }

            // full success
            $stats = $response->json('dailyStats') ?? [];

            return collect($stats)->map(function ($stat) {
                $date = Carbon::createFromFormat('Ymd', $stat['date'])->toDateString();
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

        } catch (\Exception $e) {

            Log::error('Attendance stats API request failed', [
                'error' => $e->getMessage(),
            ]);

            // Fallback row when API is down
            return [
                [
                    'id' => 'api-down',
                    'title' => '❌ API Offline',
                    'start' => now()->toDateString(),
                    'end' => now()->toDateString(),
                    'unique_entries' => 0,
                    'unique_entered_users' => 0,
                    'unique_not_paid_users' => 0,
                ]
            ];
        }
    }

}
