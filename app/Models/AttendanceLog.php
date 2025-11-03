<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceLog extends Model
{
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
    public static $itemsPerPage = 100;
    public static $totalRecords = 0;

    /**
     * Set search parameters for API request.
     */
    public static function setSearchParameters($date, $studentId = null, $page = 1, $perPage = 10)
    {
        static::$date = $date;
        static::$studentId = $studentId;
        static::$currentPage = $page;
        static::$itemsPerPage = $perPage;
        static::clearBootedModels();
    }

    /**
     * Fetch rows from API and return paginated result.
     */
    public function getRowsPaginated(): LengthAwarePaginator
    {
        if (!static::$date) {
            return new LengthAwarePaginator([], 0, static::$itemsPerPage, static::$currentPage);
        }

        try {
            $payload = [
                'date' => is_array(static::$date)
                    ? Carbon::parse(static::$date['date'] ?? now())->format('Ymd')
                    : Carbon::parse(static::$date)->format('Ymd'),
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
                ->get('http://127.0.0.1:8001/api/local-data', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['logs'] ?? [];
                static::$totalRecords = $data['pagination']['totalRecords'] ?? count($items);

                return new LengthAwarePaginator(
                    $items,
                    static::$totalRecords,
                    static::$itemsPerPage,
                    static::$currentPage,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );
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

        return new LengthAwarePaginator([], 0, static::$itemsPerPage, static::$currentPage);
    }

    /**
     * Get current items per page.
     */
    public function getPerPage(): int
    {
        return static::$itemsPerPage;
    }

    /**
     * Disable Sushi caching (if used).
     */
    protected function sushiShouldCache(): bool
    {
        return false;
    }
}
