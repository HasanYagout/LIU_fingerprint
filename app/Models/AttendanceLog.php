<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Sushi\Sushi;

class AttendanceLog extends Model
{
    use Sushi;

    protected $schema = [
        'C_Date'   => 'string',
        'C_Time'   => 'string',
        'C_Name'   => 'string',
        'C_Unique' => 'string',
        'L_Mode'   => 'integer',
        'L_Result' => 'integer',
    ];

    protected static $date;
    protected static $studentId;
    protected static $currentPage = 1;
    protected static $itemsPerPage = 20;
    // In your AttendanceLog model
    public static $apiPageSize = 100; // Default value
    public static $totalRecords = 0;

    public static function setSearchParameters($date, $studentId = null, $page = 1, $perPage = 10)
    {
        static::$date = $date;
        static::$studentId = $studentId;
        static::$currentPage = $page;
        static::$itemsPerPage = $perPage;
        static::clearBootedModels();
    }

    public function getRows(): array
    {
        if (!static::$date) {
            return [];
        }

        try {
            $payload = [
                'date' => Carbon::parse(static::$date)->format('Ymd'),
                'page' => static::$currentPage,
                'pageSize' => static::$itemsPerPage,
            ];

            if (!empty(static::$studentId)) {
                $payload['uniqueId'] = static::$studentId;
            }

            $response = Http::withBasicAuth(
                config('services.api.username'),
                config('services.api.password')
            )
                ->timeout(10)
                ->post('http://192.168.1.62:2000/api/v1/attendance-logs', $payload);
            dd($response->json());
            if ($response->successful()) {



                $data = $response->json();
                static::$totalRecords = $data['pagination']['totalRecords'] ?? 0;
                static::$apiPageSize = $data['pagination']['pageSize'] ?? 100; // Store API page size

                return $data['logs'] ?? [];
            }

            Log::error('Attendance logs API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request' => $payload,
            ]);
        } catch (\Exception $e) {
            Log::error('Attendance logs API request exception', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    public function getPerPage()
    {
        return static::$itemsPerPage;
    }

    protected function sushiShouldCache()
    {
        return false;
    }
}
