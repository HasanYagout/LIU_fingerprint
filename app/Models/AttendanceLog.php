<?php

namespace App\Models;

use App\Helpers\Helpers;
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
        'L_UID'     =>  'string',
        'L_Mode'   => 'integer',
        'L_Result' => 'integer',
    ];

    protected static $date;
    protected static $studentId;
    protected static $currentPage = 1;
    public static $lastError = null;

    protected static $itemsPerPage = 100;
    public static $totalRecords = 0;

    public static function setSearchParameters($date, $studentId = null, $page = 1, $perPage = 100)
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

            // ⛔️ Prevent UI freezing — add timeout & connection timeout
            $response = Http::timeout(3)     // max 3 seconds
            ->connectTimeout(2)          // fail connection after 2 seconds
            ->withBasicAuth(
                config('services.api.username'),
                config('services.api.password')
            )
                ->post('http://192.168.1.102:2001/api/v1/attendance-logs', $payload);

            if ($response->successful()) {
                static::$lastError = null; // clear old error
                $data = $response->json();

                static::$totalRecords = $data['pagination']['totalRecords']
                    ?? count($data['logs'] ?? []);

                return $data['logs'] ?? [];
            }





        } catch (\Exception $e) {
            Helpers::notify('Attendance service is currently unavailable.');
            return [];
        }

        // Always return empty on any kind of failure
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
