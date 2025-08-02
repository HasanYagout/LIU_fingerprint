<?php
namespace App\Jobs;

use App\Models\SemesterStudent;
use App\Models\StudentException;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckStudentExceptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $toUnblacklist = [];
        $toBlacklist = [];

        Log::info("Starting daily student status update job", ['date' => $today]);

        // Get all active exceptions (students to unblacklist)
        StudentException::with(['student', 'semester'])
            ->where('from_date', '<=', $today)
            ->where('to_date', '>=', $today)
            ->orderBy('student_id')
            ->chunk(200, function ($studentExceptions) use (&$toUnblacklist) {
                foreach ($studentExceptions as $exception) {
                    $toUnblacklist[] = $exception->student_id;
                    Log::debug("Active exception (unblacklist)", [
                        'student_id' => $exception->student_id,
                        'from_date' => $exception->from_date,
                        'to_date' => $exception->to_date,
                    ]);
                }
            });

        // Get all expired or not-yet-active exceptions (students to blacklist)
        StudentException::with(['student', 'semester'])
            ->where(function ($query) use ($today) {
                $query->where('to_date', '<', $today)    // Exception ended before today
                ->orWhere('from_date', '>', $today); // Exception starts after today
            })
            ->orderBy('student_id')
            ->chunk(200, function ($studentExceptions) use (&$toBlacklist) {
                foreach ($studentExceptions as $exception) {
                    $toBlacklist[] = $exception->student_id;
                    Log::debug("Inactive exception (blacklist)", [
                        'student_id' => $exception->student_id,
                        'from_date' => $exception->from_date,
                        'to_date' => $exception->to_date,
                    ]);
                }
            });

        // Dispatch batch jobs
        $this->dispatchBatchJobs($toUnblacklist, $toBlacklist);

        Log::info("Completed daily student status update job", [
            'unblacklisted_count' => count($toUnblacklist),
            'blacklisted_count' => count($toBlacklist),
        ]);
    }


    protected function dispatchBatchJobs(array $toUnblacklist, array $toBlacklist)
    {
        $shouldRestart = false;

        if (!empty($toUnblacklist)) {
            UnblacklistJob::dispatch(
                array_unique($toUnblacklist),
                config('services.api.username'),
                config('services.api.password')
            );
            $shouldRestart = true;
        }

        if (!empty($toBlacklist)) {
            BlacklistJob::dispatch(
                array_unique($toBlacklist),
                config('services.api.username'),
                config('services.api.password')
            );
            $shouldRestart = true;
        }

        // Dispatch restart job if any changes were made
        if ($shouldRestart) {
            RestartJob::dispatch(
                config('services.api.username'),
                config('services.api.password')
            );
        }
    }

}
