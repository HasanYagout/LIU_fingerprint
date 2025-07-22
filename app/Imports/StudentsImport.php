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

    public function __construct(int $semesterId)
    {
        $this->semesterId = $semesterId;
        $this->apiUsername = 'admin';
        $this->apiPassword = 'unis2025';
        $this->semester = Semester::findOrFail($semesterId);
    }

    public function model(array $row)
    {
        if (empty($row['student_id']) || empty($row['name'])) {
            throw new \Exception("Missing required student data");
        }

        try {
            return DB::transaction(function () use ($row) {
                $studentId = (int)$row['student_id'];
                $percentage = (int)($row['percentage'] ?? 0);

                $student = Student::updateOrCreate(
                    ['student_id' => $studentId],
                    [
                        'name' => $row['name'],
                        'major' => $row['major'] ?? null,
                        'email' => $row['email'] ?? null,
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
                'student_id' => $row['student_id'] ?? null,
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
