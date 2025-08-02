<?php
//namespace App\Models;
//
//use Illuminate\Support\Facades\Http;
//use Illuminate\Support\Carbon;
//use Illuminate\Database\Eloquent\Model;
//use Illuminate\Support\Facades\Log;
//use Sushi\Sushi;
//
//class AttendanceStat extends Model
//{
//    use Sushi;
//
//    protected $casts = [
//        'start' => 'date',
//        'end' => 'date',
//    ];
//
//    public function getRows(): array
//    {
//        $start = now()->subDays(15)->format('Ymd');
//        $end = now()->addDays(15)->format('Ymd');
//
//        try {
//            $response = Http::withBasicAuth(config('services.api.username'), config('services.api.password'))
//                ->timeout(10)
//                ->post('http://172.170.17.5:2001/api/v1/attendance-stats', [
//                    'startDate' => $start,
//                    'endDate' => $end,
//                ]);
//
//            if ($response->successful()) {
//                $stats = $response->json('dailyStats') ?? [];
//
//                return collect($stats)->map(function ($stat) {
//                    $date = Carbon::createFromFormat('Ymd', $stat['date'])->toDateString();
//
//                    return [
//                        'id' => $stat['date'],
//                        'title' => "Entries: {$stat['uniqueEntries']} (Unpaid: {$stat['uniqueNotPaidUsers']})",
//                        'start' => $date,
//                        'end' => $date,
//                    ];
//                })->toArray();
//            }
//
//            Log::error('Attendance stats API request failed', [
//                'status' => $response->status(),
//                'response' => $response->body(),
//            ]);
//        } catch (\Exception $e) {
//            Log::error('Attendance stats API request exception', [
//                'error' => $e->getMessage(),
//            ]);
//        }
//
//        return [];
//    }
//}
namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class AttendanceStat extends Model
{
    use Sushi;

    protected $casts = [
        'start' => 'date',
        'end' => 'date',
    ];

    public function getRows(): array
    {
        // Static data matching your API response format
        return [
            [
                'id' => '20250722',
                'title' => "Entries: 4 (Unpaid: 2)",
                'start' => '2025-07-22',
                'end' => '2025-07-22',
            ],
            [
                'id' => '20250725',
                'title' => "Entries: 5 (Unpaid: 5)",
                'start' => '2025-07-25',
                'end' => '2025-07-25',
            ],
            [
                'id' => '20250727',
                'title' => "Entries: 6 (Unpaid: 4)",
                'start' => '2025-07-27',
                'end' => '2025-07-27',
            ],
        ];
    }
}
