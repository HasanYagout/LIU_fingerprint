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
            $response = Http::withBasicAuth(config('services.api.username'), config('services.api.password'))
                ->timeout(10)
                ->post('http://192.168.1.102:2001/api/v1/attendance-stats', [
                    'startDate' => $startFormatted,
                    'endDate' => $endFormatted,
                ]);

            if ($response->successful() && $response->json('success')) {
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
            }

            Log::error('Attendance stats API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {

            Log::error('Attendance stats API request exception', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }
}
