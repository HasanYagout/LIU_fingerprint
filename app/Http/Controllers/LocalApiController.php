<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LocalApiController extends Controller
{
    public function index(Request $request)
    {
        // 📥 Read query parameters
        $date = $request->input('date', Carbon::now()->format('Ymd'));
        $studentId = $request->input('student_id'); // filter by student_id
        $page = max((int) $request->input('page', 1), 1);
        $pageSize = max((int) $request->input('pageSize', 100), 1);

        // 🧪 Example fake data generation
        $logs = [];
        $totalRecords = 60; // total entries to generate
        $studentCount = 5; // number of unique students

        // Generate unique students
        $students = [];
        for ($s = 1; $s <= $studentCount; $s++) {
            $yearDigits = substr(Carbon::now()->format('y'), -2);
            $L_UID = '6' . $yearDigits . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $students[$s] = [
                'id' => $s,
                'name' => "Student $s",
                'L_UID' => $L_UID,
            ];
        }

        // Generate logs
        for ($i = 0; $i < $totalRecords; $i++) {
            $studentKey = rand(1, $studentCount);
            $student = $students[$studentKey];

            // If student_id filter is set, skip others
            if ($studentId && $student['id'] != $studentId) {
                continue;
            }

            $time = Carbon::createFromFormat('His', sprintf('%06d', 205635 + ($i * 60)))->format('His');

            $logs[] = [
                'C_Date' => $date,
                'C_Time' => $time,
                'L_TID' => rand(1, 10),
                'L_UID' => $student['L_UID'],
                'C_Name' => $student['name'],
                'C_Unique' => $student['L_UID'],
                'C_Office' => '****',
                'C_Post' => '****',
                'C_Card' => rand(0, 1) ? '' : (string)rand(10000000, 99999999),
                'L_UserType' => 0,
                'L_Mode' => rand(1, 4),
                'L_MatchingType' => rand(0, 3),
                'L_Result' => rand(0, 3),
                'L_IsPicture' => rand(0, 1),
                'L_Device' => 0,
                'L_OverCount' => 0,
                'C_Property' => '0000',
                'L_JobCode' => 0,
                'L_Etc' => rand(2, 255),
                'L_Trans' => 0,
                'D_Latitude' => 0,
                'D_Longitude' => 0,
                'C_MobilePhone' => '',
            ];
        }

        // 📊 Simulated statistics
        $statistics = [
            'totalEntries' => count($logs),
            'uniqueUsers' => count(array_unique(array_column($logs, 'L_UID'))),
            'uniqueDevices' => 1,
            'firstEntryTime' => $logs[0]['C_Time'] ?? null,
            'lastEntryTime' => end($logs)['C_Time'] ?? null,
            'averageEntriesPerUser' => count($logs),
            'uniqueEnteredUsers' => count(array_unique(array_column($logs, 'L_UID'))),
            'uniqueNotPaidUsers' => 1,
        ];

        // 🔢 Pagination setup (mocked)
        $pagination = [
            'currentPage' => $page,
            'pageSize' => $pageSize,
            'totalRecords' => count($logs),
            'totalPages' => 1,
            'hasNextPage' => false,
            'hasPreviousPage' => false,
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
            'logs' => $logs,
            'timestamp' => now()->toISOString(),
        ]);
    }

}
