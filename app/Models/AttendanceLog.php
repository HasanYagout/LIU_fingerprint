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
    protected $table = null;
    protected $connection = 'none';

    // Override newBaseQueryBuilder to stop SQL queries
    protected function newBaseQueryBuilder()
    {
        throw new \Exception('AttendanceLog does not use the database.');
    }

    protected static $date;
    protected static $studentId;
    protected static $currentPage = 1;
    public static $itemsPerPage = 30;
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
            // Build query parameters for GET request
            $queryParams = [
                'date' => Carbon::parse(static::$date)->format('Ymd'),
                'page' => static::$currentPage,
                'pageSize' => static::$itemsPerPage,
            ];

            // Add student_id to query params if provided
            if (!empty(static::$studentId)) {
                $queryParams['student_id'] = static::$studentId;
            }

            // Build the URL with query parameters
            $url = 'http://127.0.0.1:8001/api/local-data?' . http_build_query($queryParams);

            // Debug the API request
            \Log::info('Making API Request:', [
                'url' => $url,
                'query_params' => $queryParams,
                'student_id' => static::$studentId
            ]);

            $response = Http::withBasicAuth(
                config('services.api.username', 'admin'),
                config('services.api.password', 'password')
            )->timeout(30)
                ->get($url); // No need to pass payload separately for GET

            if ($response->successful()) {
                $data = $response->json();

                \Log::info('API Response Received:', [
                    'success' => $data['success'] ?? false,
                    'total_logs' => count($data['logs'] ?? []),
                    'pagination' => $data['pagination'] ?? [],
                    'search_filter' => $data['searchFilter'] ?? []
                ]);

                $items = $data['logs'] ?? [];
                $pagination = $data['pagination'] ?? [];

                $totalRecords = $pagination['totalRecords'] ?? count($items);
                $currentPage = $pagination['currentPage'] ?? static::$currentPage;
                $pageSize = $pagination['pageSize'] ?? static::$itemsPerPage;

                static::$totalRecords = $totalRecords;

                return new LengthAwarePaginator(
                    $items,
                    $totalRecords,
                    $pageSize,
                    $currentPage,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );
            } else {
                \Log::error('API Request Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Attendance logs API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
}
