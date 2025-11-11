<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Jobs\BlacklistJob;
use App\Jobs\RestartJob;
use App\Jobs\UnblacklistJob;
use App\Models\Semester;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithEvents
{
    protected int $semesterId;
    protected string $apiUsername;
    protected string $apiPassword;
    protected array $toBlacklist = [];
    protected array $toUnblacklist = [];
    protected Semester $semester;
    protected int $requiredPercentage;

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
        $this->requiredPercentage = Helpers::getRequiredPercentage($semesterId);
        $this->toUnblacklist = $this->ignoredIds;
    }

    public function collection(Collection $rows)
    {
        $insertData = [];
        $semesterPivotData = [];

        foreach ($rows as $row) {
            if (empty($row['id']) || empty($row['name'])) {
                Log::warning('Skipping row with missing data', ['row' => $row]);
                continue;
            }

            $studentId = (int)$row['id'];

            // Skip ignored IDs
            if (in_array($studentId, $this->ignoredIds, true)) {
                Log::info("Skipping ignored student ID {$studentId}");
                continue;
            }

            $percentage = isset($row['paid']) ? (int)$row['paid'] : 0;

            // Prepare bulk insert/update for students
            $insertData[] = [
                'student_id' => $studentId,
                'name'       => $row['name'],
                'major'      => $row['major'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Prepare pivot table data
            $semesterPivotData[$studentId] = ['percentage' => $percentage];

            // Categorize for blacklist/unblacklist
            if ($percentage >= $this->requiredPercentage) {
                $this->toUnblacklist[] = $studentId;
            } else {
                $this->toBlacklist[] = $studentId;
            }
        }

        // Bulk upsert students
        if (!empty($insertData)) {
            Student::upsert(
                $insertData,
                ['student_id'],            // unique key
                ['name', 'major', 'updated_at'] // columns to update
            );
        }

        // Bulk attach to semester pivot table
        if (!empty($semesterPivotData)) {
            // Avoid repeated DB queries by using insertOrIgnore for pivot
            $existing = DB::table('semester_student')
                ->where('semester_id', $this->semesterId)
                ->whereIn('student_id', array_keys($semesterPivotData))
                ->pluck('student_id')
                ->toArray();

            $toInsert = [];
            foreach ($semesterPivotData as $studentId => $pivot) {
                if (!in_array($studentId, $existing)) {
                    $toInsert[] = [
                        'semester_id' => $this->semesterId,
                        'student_id'  => $studentId,
                        'percentage'  => $pivot['percentage'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                } else {
                    // Update existing pivot percentages in bulk
                    DB::table('semester_student')
                        ->where('semester_id', $this->semesterId)
                        ->where('student_id', $studentId)
                        ->update(['percentage' => $pivot['percentage'], 'updated_at' => now()]);
                }
            }

            if (!empty($toInsert)) {
                DB::table('semester_student')->insert($toInsert);
            }
        }
    }

    public function registerEvents(): array
    {
        return [];
//        return [
//            AfterImport::class => function () {
//                $this->toUnblacklist = array_unique(array_merge($this->toUnblacklist, $this->ignoredIds));
//
//                if (!empty($this->toBlacklist)) {
//                    BlacklistJob::dispatch($this->toBlacklist, $this->apiUsername, $this->apiPassword);
//                }
//
//                if (!empty($this->toUnblacklist)) {
//                    UnblacklistJob::dispatch($this->toUnblacklist, $this->apiUsername, $this->apiPassword);
//                }
//
//                RestartJob::dispatch($this->apiUsername, $this->apiPassword);
//            },
//        ];
    }

    public function chunkSize(): int
    {
        return 500; // adjust based on memory and file size
    }
}
