<?php

namespace App\Imports;

use App\Jobs\BlacklistJob;
use App\Jobs\RestartJob;
use App\Jobs\UnblacklistJob;
use App\Models\Semester;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StudentsImport implements ToModel, WithHeadingRow, WithEvents
{
    protected int $semesterId;
    protected string $apiUsername;
    protected string $apiPassword;
    protected array $toBlacklist = [];
    protected array $toUnblacklist = [];
    protected Semester $semester;
    protected array $ignoredIds = [
        62030104,
        62110453,
        62110155,
        62130320,
        62110105,
        62130067
    ];

    public function __construct(int $semesterId)
    {
        $this->semesterId = $semesterId;
        $this->apiUsername = config('services.api.username');
        $this->apiPassword = config('services.api.password');
        $this->semester = Semester::findOrFail($semesterId);

        // Initialize unblacklist with ignored IDs
        $this->toUnblacklist = $this->ignoredIds;
    }

    public function model(array $row)
    {
        if (empty($row['id']) || empty($row['name'])) {
            throw new \Exception("Missing required student data");
        }
        $studentId = (int)$row['id'];

        // Skip processing if this ID is in the ignored list
        if (in_array($studentId, $this->ignoredIds)) {
            Log::info("Skipping ignored student ID", ['student_id' => $studentId]);
            return null;
        }

        try {
            return DB::transaction(function () use ($row) {
                $studentId = (int)$row['id'];
                $percentage = (int)($row['paid'] ?? 0);
                $student = Student::updateOrCreate(
                    ['student_id' => $studentId],
                    [
                        'name' => $row['name'],
                        'major' => $row['major'] ?? null,
                    ]
                );

                // Determine required percentage based on semester dates
                $requiredPercentage = $this->getRequiredPercentage();

                $semester = $this->semester;
                $semester->students()->syncWithoutDetaching([
                    $student->student_id => ['percentage' => $percentage],
                ]);

                // Categorize students
                if ($percentage >= $requiredPercentage) {
                    $this->toUnblacklist[] = $student->student_id;
                } else {
                    $this->toBlacklist[] = $student->student_id;
                }

                return $student;
            });
        } catch (\Exception $e) {
            Log::error("Failed to process student", [
                'student_id' => $row['id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function getRequiredPercentage(): int
    {
        $now = Carbon::now();
        $startDate = Carbon::parse($this->semester->start_date);
        $midtermDate = Carbon::parse($this->semester->midterm_date);
        $endDate = Carbon::parse($this->semester->end_date);

        // Two weeks before midterm (midterm threshold)
        $midtermThreshold = $midtermDate->copy()->subWeeks(2);
        // Two weeks before final (final threshold)
        $finalThreshold = $endDate->copy()->subWeeks(2);

        if ($now->gte($finalThreshold)) {
            // After final threshold (two weeks before final) - require 100%
            return 100;
        } elseif ($now->gte($midtermThreshold)) {
            // After midterm threshold (two weeks before midterm) but before final threshold - require 50%
            return 50;
        } else {
            // Before midterm threshold - require 0%
            return 0;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                // Ensure ignored IDs are always in unblacklist (remove duplicates)
                $this->toUnblacklist = array_unique(
                    array_merge($this->toUnblacklist, $this->ignoredIds)
                );

                // Dispatch blacklist/unblacklist jobs for all students
                if (!empty($this->toBlacklist)) {
                    BlacklistJob::dispatch(
                        $this->toBlacklist,
                        $this->apiUsername,
                        $this->apiPassword
                    );
                }

                if (!empty($this->toUnblacklist)) {
                    UnblacklistJob::dispatch(
                        $this->toUnblacklist,
                        $this->apiUsername,
                        $this->apiPassword
                    );
                }

                // Dispatch restart job once after all processing is done
                RestartJob::dispatch($this->apiUsername, $this->apiPassword);
            },
        ];
    }
}
