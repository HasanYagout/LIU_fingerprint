<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\StudentException;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CheckStudentExceptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info("Starting daily student status check");

        $today = Carbon::today()->toDateString();
        $toUnblacklist = [];
        $toBlacklist = [];

        // Step 1: Get all students with active exceptions today
        $studentsWithActiveExceptions = StudentException::whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->pluck('student_id')
            ->unique()
            ->toArray();

        Log::debug("Students with active exceptions", [
            'count' => count($studentsWithActiveExceptions),
            'student_ids' => $studentsWithActiveExceptions
        ]);

        // Step 2: Get active semester
        $activeSemester = \App\Models\Semester::where('status', 1)->first();

        if (!$activeSemester) {
            Log::error("No active semester found");
            return;
        }

        // Step 3: Get ALL students in active semester
        $students = Student::whereHas('semesters', fn($q) => $q->where('status', 1))
            ->with(['semesters' => fn($q) => $q->where('status', 1)])
            ->get();

        Log::info("Processing all students in active semester", ['count' => $students->count()]);

        // Step 4: Check each student's status
        foreach ($students as $student) {
            try {
                $studentId = $student->student_id;

                // If student has active exception, unblacklist
                if (in_array($studentId, $studentsWithActiveExceptions)) {
                    $toUnblacklist[] = $studentId;
                    Log::debug("Student with active exception - unblacklist", [
                        'student_id' => $studentId,
                        'reason' => 'active_exception'
                    ]);
                    continue;
                }

                // Get academic status
                $status = \App\Helpers\Helpers::getStatus($studentId, $activeSemester->id);

                if (!$status) {
                    Log::warning("getStatus returned null or invalid for student", [
                        'student_id' => $studentId,
                    ]);
                    continue;
                }

                if ($status['status'] === 'success') {
                    $toUnblacklist[] = $studentId;
                } else {
                    $toBlacklist[] = $studentId;
                }

            } catch (\Throwable $e) {
                Log::error("Error processing student", [
                    'student_id' => $student->student_id ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Step 5: Remove duplicates and ensure no overlap
        $toUnblacklist = array_values(array_unique($toUnblacklist));
        $toBlacklist = array_values(array_unique($toBlacklist));
        $toBlacklist = array_diff($toBlacklist, $toUnblacklist);
         
        // Step 6: Process API calls
        $this->processStudentStatusChanges($toUnblacklist, $toBlacklist);

        Log::info("Completed student status check", [
            'unblacklisted_count' => count($toUnblacklist),
            'blacklisted_count' => count($toBlacklist),
            'unblacklisted_ids' => $toUnblacklist,
            'blacklisted_ids' => $toBlacklist,
        ]);
    }

    protected function processStudentStatusChanges(array $toUnblacklist, array $toBlacklist)
    {
        $username = config('services.api.username');
        $password = config('services.api.password');

        // Unblacklist
        if (!empty($toUnblacklist)) {
            try {
                $response = Http::withBasicAuth($username, $password)
                    ->post('http://170.170.17.6:2001/api/v1/unblacklist', [
                        'studentIds' => $toUnblacklist,
                        'timestamp' => Carbon::now()->toDateTimeString(),
                    ]);

                Log::info("Unblacklist processed", [
                    'count' => count($toUnblacklist),
                    'student_ids' => $toUnblacklist,
                    'response' => $response->json()
                ]);
            } catch (\Exception $e) {
                Log::error("Unblacklist failed", [
                    'error' => $e->getMessage(),
                    'student_ids' => $toUnblacklist
                ]);
            }
        }

        // Blacklist
        if (!empty($toBlacklist)) {
            try {
                $response = Http::withBasicAuth($username, $password)
                    ->post('http://170.170.17.6:2001/api/v1/blacklist', [
                        'studentIds' => $toBlacklist,
                        'timestamp' => Carbon::now()->toDateTimeString(),
                    ]);

                Log::info("Blacklist processed", [
                    'count' => count($toBlacklist),
                    'student_ids' => $toBlacklist,
                    'response' => $response->json()
                ]);
            } catch (\Exception $e) {
                Log::error("Blacklist failed", [
                    'error' => $e->getMessage(),
                    'student_ids' => $toBlacklist
                ]);
            }
        }

        // Restart services if anything changed
        if (!empty($toUnblacklist) || !empty($toBlacklist)) {
            try {
                Http::withBasicAuth($username, $password)
                    ->post('http://170.170.17.6:2001/api/v1/restart-services');

                Log::info("Service restart completed");
            } catch (\Exception $e) {
                Log::error("Restart failed", ['error' => $e->getMessage()]);
            }
        }
    }
}
