<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LocalApiController extends Controller
{
    public function index(Request $request)
    {
        // Read query parameters
        $date = $request->input('date'); // expected format: Ymd
        $page = max((int) $request->input('page', 1), 1);
        $pageSize = max((int) $request->input('pageSize', 20), 1);

        // Example results and faker setup
        $results = ['Positive', 'Negative', 'Pending'];
        $allRecords = [];

        // Generate 500 random records
        for ($i = 1; $i <= 500; $i++) {
            $recordDate = Carbon::now()->subDays(rand(0, 30));
            $allRecords[] = [
                'C_date'   => $recordDate->toDateString(),         // YYYY-MM-DD
                'c_time'   => $recordDate->format('H:i:s'),
                'c_name'   => 'User ' . $i,
                'c_unique' => strtoupper(Str::random(6)),
                'l_result' => $results[array_rand($results)],
                'l_tid'    => 'TID' . str_pad($i, 3, '0', STR_PAD_LEFT),
            ];
        }

        // Filter by date if provided
        if ($date) {
            $filterDate = Carbon::createFromFormat('Ymd', $date)->toDateString();
            $allRecords = array_filter($allRecords, fn($r) => $r['C_date'] === $filterDate);
        }

        // Paginate manually
        $total = count($allRecords);
        $paginated = array_slice($allRecords, ($page - 1) * $pageSize, $pageSize);

        // Return paginated response
        return response()->json([
            'currentPage' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'data' => array_values($paginated),
        ]);
    }
}
