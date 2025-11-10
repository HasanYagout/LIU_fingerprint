<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LocalApiController extends Controller
{
    public function index(Request $request)
    {
        // 📥 Read query parameters
        $date = $request->input('date', Carbon::now()->format('Ymd'));
        $studentId = $request->input('student_id'); // filter by student_id
        $page = max((int) $request->input('page', 1), 1);
        $pageSize = max((int) $request->input('pageSize', 30), 1);

        // 🧪 Static student data - FIXED IDs that won't change
        $students = [
            1 => [
                'id' => 1,
                'name' => "Student 1",
                'L_UID' => '62570490',
                'C_Unique' => '62570490',
            ],
            2 => [
                'id' => 2,
                'name' => "Student 2",
                'L_UID' => '62560218',
                'C_Unique' => '62560218',
            ],
            3 => [
                'id' => 3,
                'name' => "Student 3",
                'L_UID' => '62597414',
                'C_Unique' => '62597414',
            ],
            4 => [
                'id' => 4,
                'name' => "Student 4",
                'L_UID' => '62526472',
                'C_Unique' => '62526472',
            ],
            5 => [
                'id' => 5,
                'name' => "Student 5",
                'L_UID' => '62543005',
                'C_Unique' => '62543005',
            ],
        ];

        // 🧪 Static logs data with STATIC dates
        $allLogs = [
            [
                'C_Date' => '20251109',
                'C_Time' => '205635',
                'L_TID' => 4,
                'L_UID' => '62543005',
                'C_Name' => 'Student 5',
                'C_Unique' => '62543005',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '',
                'L_UserType' => 0,
                'L_Mode' => 3,
                'L_MatchingType' => 0,
                'L_Result' => 2,
                'L_IsPicture' => 1,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 91,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20241102',
                'C_Time' => '205735',
                'L_TID' => 3,
                'L_UID' => '62543005',
                'C_Name' => 'Student 5',
                'C_Unique' => '62543005',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '97853483',
                'L_UserType' => 0,
                'L_Mode' => 1,
                'L_MatchingType' => 0,
                'L_Result' => 0,
                'L_IsPicture' => 0,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 117,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251104',
                'C_Time' => '205755',
                'L_TID' => 9,
                'L_UID' => '62597414',
                'C_Name' => 'Student 3',
                'C_Unique' => '62597414',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '',
                'L_UserType' => 0,
                'L_Mode' => 2,
                'L_MatchingType' => 1,
                'L_Result' => 3,
                'L_IsPicture' => 1,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 51,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251104',
                'C_Time' => '205815',
                'L_TID' => 8,
                'L_UID' => '62543005',
                'C_Name' => 'Student 5',
                'C_Unique' => '62543005',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '63711760',
                'L_UserType' => 0,
                'L_Mode' => 2,
                'L_MatchingType' => 3,
                'L_Result' => 2,
                'L_IsPicture' => 1,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 176,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251104',
                'C_Time' => '205915',
                'L_TID' => 10,
                'L_UID' => '62560218',
                'C_Name' => 'Student 2',
                'C_Unique' => '62560218',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '66361357',
                'L_UserType' => 0,
                'L_Mode' => 4,
                'L_MatchingType' => 2,
                'L_Result' => 0,
                'L_IsPicture' => 1,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 109,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251108',
                'C_Time' => '205935',
                'L_TID' => 7,
                'L_UID' => '62543005',
                'C_Name' => 'Student 5',
                'C_Unique' => '62543005',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '74525229',
                'L_UserType' => 0,
                'L_Mode' => 4,
                'L_MatchingType' => 0,
                'L_Result' => 0,
                'L_IsPicture' => 1,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 200,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251102',
                'C_Time' => '210035',
                'L_TID' => 2,
                'L_UID' => '62560218',
                'C_Name' => 'Student 2',
                'C_Unique' => '62560218',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '',
                'L_UserType' => 0,
                'L_Mode' => 2,
                'L_MatchingType' => 0,
                'L_Result' => 3,
                'L_IsPicture' => 0,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 167,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251103',
                'C_Time' => '210055',
                'L_TID' => 7,
                'L_UID' => '62597414',
                'C_Name' => 'Student 3',
                'C_Unique' => '62597414',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '',
                'L_UserType' => 0,
                'L_Mode' => 2,
                'L_MatchingType' => 2,
                'L_Result' => 0,
                'L_IsPicture' => 1,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 227,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251104',
                'C_Time' => '210115',
                'L_TID' => 10,
                'L_UID' => '62526472',
                'C_Name' => 'Student 4',
                'C_Unique' => '62526472',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '',
                'L_UserType' => 0,
                'L_Mode' => 4,
                'L_MatchingType' => 3,
                'L_Result' => 1,
                'L_IsPicture' => 0,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 203,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
            [
                'C_Date' => '20251105',
                'C_Time' => '210215',
                'L_TID' => 7,
                'L_UID' => '62560218',
                'C_Name' => 'Student 2',
                'C_Unique' => '62560218',
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => '',
                'L_UserType' => 0,
                'L_Mode' => 1,
                'L_MatchingType' => 1,
                'L_Result' => 1,
                'L_IsPicture' => 0,
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => 21,
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ],
        ];

        // 🔍 Filter by student_id if provided
        if (!empty($studentId)) {
            $filteredLogs = array_filter($allLogs, function($log) use ($studentId) {
                // Convert both to string for comparison since student_id might come as string
                return (string)$log['C_Unique'] === (string)$studentId;
            });

            $logs = array_values($filteredLogs); // Reindex array
        } else {
            $logs = $allLogs;
        }

        // 🔍 Filter by date if provided
        if (!empty($date)) {
            $filteredLogs = array_filter($logs, function($log) use ($date) {
                return $log['C_Date'] === $date;
            });
            $logs = array_values($filteredLogs);
        }

        $totalRecords = count($logs);
        $totalPages = (int) ceil($totalRecords / $pageSize);
        $page = max(min($page, $totalPages), 1); // clamp page number

        $offset = ($page - 1) * $pageSize;
        $pagedLogs = array_slice($logs, $offset, $pageSize);

        // 📊 Simulated statistics
        $statistics = [
            'totalEntries' => count($logs),
            'uniqueUsers' => count(array_unique(array_column($logs, 'L_UID'))),
            'uniqueDevices' => 1,
            'firstEntryTime' => $logs[0]['C_Time'] ?? null,
            'lastEntryTime' => end($logs)['C_Time'] ?? null,
            'averageEntriesPerUser' => count($logs) > 0 ? count($logs) / count(array_unique(array_column($logs, 'L_UID'))) : 0,
            'uniqueEnteredUsers' => count(array_unique(array_column($logs, 'L_UID'))),
            'uniqueNotPaidUsers' => 1,
        ];

        $pagination = [
            'currentPage'     => $page,
            'pageSize'        => $pageSize,
            'totalRecords'    => $totalRecords,
            'totalPages'      => $totalPages,
            'hasNextPage'     => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
        ];

        // 📤 Final JSON response
        return response()->json([
            'success' => true,
            'message' => 'Attendance logs retrieved successfully',
            'date' => $date,
            'searchFilter' => [
                'student_id' => $studentId,
            ],
            'pagination' => $pagination,
            'statistics' => $statistics,
            'logs' => $pagedLogs,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * @throws ConnectionException
     */
    public function stat(Request $request)
    {

        return response()->json([
            "success" => true,
            "message" => "Attendance statistics retrieved successfully",
            "dateRange" => [
                "startDate" => "20250621",
                "endDate" => "20251127"
            ],
            "statistics" => [
                "totalDays" => 3,
                "totalUniqueEntries" => 15,
                "averageDailyEntries" => 5
            ],
            "dailyStats" => [
                [
                    "date" => "20251110",
                    "uniqueEntries" => 4,
                    "uniqueEnteredUsers" => 4,
                    "uniqueNotPaidUsers" => 2
                ],
                [
                    "date" => "20251109",
                    "uniqueEntries" => 5,
                    "uniqueEnteredUsers" => 5,
                    "uniqueNotPaidUsers" => 5
                ],
                [
                    "date" => "20250727",
                    "uniqueEntries" => 6,
                    "uniqueEnteredUsers" => 6,
                    "uniqueNotPaidUsers" => 4
                ]
            ],
            "timestamp" => "2025-07-27T18:48:23.551Z"
        ]);
    }

}
