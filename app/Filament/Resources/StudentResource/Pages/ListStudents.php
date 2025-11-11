<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use App\Models\Student;
use App\Models\StudentException;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\UnblacklistJob;
use App\Jobs\BlacklistJob;
use App\Jobs\RestartJob; // If it exists

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return 'full';
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // Custom action to run your function
            Actions\Action::make('runFunction')
                ->label('Refresh Status')
                ->button() // Makes it a button
                ->color('primary') // Optional: button color
                ->action(function () {
                    // Call your function here
                    $this->myCustomFunction();
                }),
        ];
    }

    protected function myCustomFunction(): void
    {
        Log::info("Starting daily student status check");
        $today = Carbon::today()->toDateString();
        $toUnblacklist = [];
        $toBlacklist = [];

        Log::info("Starting daily student status check", ['date' => $today]);

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
        
                if (in_array($studentId, $studentsWithActiveExceptions)) {
                    $toUnblacklist[] = $studentId;
                    Log::debug("Student with active exception - unblacklist", [
                        'student_id' => $studentId,
                        'reason' => 'active_exception'
                    ]);
                    continue;
                }
        
                $status = \App\Helpers\Helpers::getStatus($studentId, $activeSemester->id);
        
                if (!$status) {
                    Log::warning("getStatus returned null or invalid for student", [
                        'student_id' => $studentId,
                    ]);
                    continue;
                }
        
                if ($status['status'] === 'success') {
                    $toUnblacklist[] = $studentId;
                    // Log::debug("Student academically active - unblacklist", [
                    //     'student_id' => $studentId,
                    //     'reason' => 'academically_active'
                    // ]);
                } else {
                    $toBlacklist[] = $studentId;
                    // Log::debug("Student academically inactive - blacklist", [
                    //     'student_id' => $studentId,
                    //     'reason' => 'academically_inactive'
                    // ]);
                }
            } catch (\Throwable $e) {
                Log::error("Error processing student in CheckStudentExceptions", [
                    'student_id' => $student->student_id ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Step 5: Remove duplicates and ensure no overlap
        $toUnblacklist = array_values(array_unique($toUnblacklist));
        $toBlacklist = array_values(array_unique($toBlacklist));

        // Final safety check - remove any students from blacklist if they're in unblacklist
        $toBlacklist = array_diff($toBlacklist, $toUnblacklist);
        $shouldRestart = false;

        if (!empty($toUnblacklist)) {
            UnblacklistJob::dispatch(
                $toUnblacklist,
                config('services.api.username'),
                config('services.api.password')
            );
            $shouldRestart = true;
            Log::info("📤 Dispatching UnblacklistJob", ['count' => count($toUnblacklist)]);
        }
    
        if (!empty($toBlacklist)) {
            BlacklistJob::dispatch(
                $toBlacklist,
                config('services.api.username'),
                config('services.api.password')
            );
            $shouldRestart = true;
            Log::info("📤 Dispatching BlacklistJob", ['count' => count($toBlacklist)]);
        }
    
        if ($shouldRestart) {
            RestartJob::dispatch(
                config('services.api.username'),
                config('services.api.password')
            );
            Log::info("🔄 Dispatching RestartJob");
        }
       
    }
}
