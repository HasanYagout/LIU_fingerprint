<?php

namespace App\Jobs;

use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckStudentExceptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, InteractsWithQueue;

    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->username = config('services.api.username');
        $this->password = config('services.api.password');
    }

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();
        Log::info("🔍 Running student status check for {$today}");

        // ✅ Preload active exceptions once
        $activeExceptions = StudentException::whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->pluck('student_id')
            ->toArray();

        $activeExceptions = array_flip($activeExceptions); // faster lookup

        // ✅ Get active semester
        $semester = Semester::where('status', 1)->first();
        if (!$semester) {
            Log::error("❌ No active semester found");
            return;
        }

        $totalBlacklisted = 0;
        $totalUnblacklisted = 0;

        // ✅ Stream students in chunks (prevents memory overflow)
        Student::whereHas('semesters', fn($q) => $q->where('status', 1))
            ->chunk(500, function ($students) use (
                $activeExceptions,
                $semester,
                &$totalBlacklisted,
                &$totalUnblacklisted
            ) {

                $toBlacklist = [];
                $toUnblacklist = [];

                foreach ($students as $student) {
                    $id = $student->student_id;

                    // ✅ Automatic unblacklist if exception active
                    if (isset($activeExceptions[$id])) {
                        $toUnblacklist[] = $id;
                        continue;
                    }

                    // ✅ API call for status
                    $status = \App\Helpers\Helpers::getStatus($id, $semester->id);

                    if (!$status || $status['status'] !== 'success') {
                        $toBlacklist[] = $id;
                    } else {
                        $toUnblacklist[] = $id;
                    }
                }

                // ✅ Send requests only if needed
                if (!empty($toBlacklist)) {

//                    $this->apiCall('blacklist', $toBlacklist);
                    $totalBlacklisted += count($toBlacklist);
                }
                if (!empty($toUnblacklist)) {
//                    $this->apiCall('unblacklist', $toUnblacklist);
                    $totalUnblacklisted += count($toUnblacklist);
                }

            });

        // ✅ Restart microservices after ALL processing
        if ($totalBlacklisted > 0 || $totalUnblacklisted > 0) {
            $this->restartServices();
        }

        Log::info("✅ Student check completed", [
            'blacklisted' => $totalBlacklisted,
            'unblacklisted' => $totalUnblacklisted
        ]);
    }

    protected function apiCall(string $endpoint, array $ids): void
    {
        try {
            Http::withBasicAuth($this->username, $this->password)
                ->post("http://192.168.1.102:2001/api/v1/{$endpoint}", [
                    'studentIds' => $ids,
                    'timestamp' => Carbon::now()->toDateTimeString()
                ]);

            Log::info("✅ {$endpoint} processed", ['count' => count($ids)]);

        } catch (\Throwable $e) {
            Log::error("❌ {$endpoint} failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function restartServices(): void
    {
        try {
            Http::withBasicAuth($this->username, $this->password)
                ->post("http://192.168.1.102:2001/api/v1/restart-services");

            Log::info("🔄 Services restarted");
        } catch (\Throwable $e) {
            Log::error("❌ Restart failed", ['error' => $e->getMessage()]);
        }
    }
}
