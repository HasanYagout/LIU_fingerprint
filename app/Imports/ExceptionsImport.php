<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Jobs\BlacklistJob;
use App\Jobs\RestartJob;
use App\Jobs\UnblacklistJob;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExceptionsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithEvents
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
//        $this->requiredPercentage = Helpers::getRequiredPercentage($semesterId);
        $this->toUnblacklist = $this->ignoredIds;
    }



public function collection(Collection $rows)
{
    $pivotData = [];
    $userId = Auth::id() ?? null;

    // Get existing exceptions pivot: student_id => [reason, from_date, to_date]
    $existingExceptions = $this->semester
        ->exceptions()
        ->get()
        ->keyBy('student_id')
        ->map(function ($student) {
            return [
                'reason'    => $student->pivot->reason,
                'from_date' => $student->pivot->from_date,
                'to_date'   => $student->pivot->to_date,
                'created_by'=> $student->pivot->created_by ?? null,

            ];
        })
        ->toArray();
    // Students actually enrolled in this semester
    $existingSemesterStudents = $this->semester
        ->students()
        ->pluck('students.student_id')
        ->toArray();

    foreach ($rows as $row) {

        if (empty($row['student_id'])) {
            Log::warning("Skipping exception row due to missing student_id", ['row' => $row]);
            continue;
        }

        $studentId = (int) $row['student_id'];

        if (in_array($studentId, $this->ignoredIds, true)) {
            Log::info("Skipping ignored exception student ID {$studentId}");
            continue;
        }



        $reason = $row['reason'] ?? null;

        // Convert Excel serial numbers to proper dates
        $fromDate = $row['from_date'] ?? null;
        $toDate   = $row['to_date'] ?? null;

        try {
            // Parse FROM DATE
            if (is_numeric($fromDate)) {
                // Excel serial number
                $fromDate = Carbon::instance(
                    ExcelDate::excelToDateTimeObject($fromDate)
                )->format('Y/m/d');
            } elseif (!empty($fromDate)) {

                // dd/mm/YYYY format
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $fromDate)) {
                    $fromDate = Carbon::createFromFormat('d/m/Y', $fromDate)
                        ->format('Y/m/d');
                } else {
                    // fallback parsing
                    $fromDate = Carbon::parse($fromDate)->format('Y/m/d');
                }
            }

            // Parse TO DATE
            if (is_numeric($toDate)) {
                $toDate = Carbon::instance(
                    ExcelDate::excelToDateTimeObject($toDate)
                )->format('Y/m/d');
            } elseif (!empty($toDate)) {

                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $toDate)) {
                    $toDate = Carbon::createFromFormat('d/m/Y', $toDate)
                        ->format('Y/m/d');
                } else {
                    $toDate = Carbon::parse($toDate)->format('Y/m/d');
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to parse dates for student {$studentId}", [
                'from_date' => $row['from_date'],
                'to_date' => $row['to_date'],
                'error' => $e->getMessage()
            ]);
            $fromDate = $toDate = null;
        }


        $existing = $existingExceptions[$studentId] ?? [];


        if (
            !isset($existingExceptions[$studentId]) ||
            $existing['reason'] !== $reason ||
            $existing['from_date'] !== $fromDate ||
            $existing['to_date'] !== $toDate
        ) {
            $pivotData[$studentId] = [
                'reason'    => $reason,
                'from_date' => $fromDate,
                'to_date'   => $toDate,
                'created_by' => $userId
            ];
        }

        $this->toUnblacklist[] = $studentId;
    }
    if (!empty($pivotData)) {
        $this->semester->exceptions()->syncWithoutDetaching($pivotData);
    }
}


    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                $this->toUnblacklist = array_unique(array_merge($this->toUnblacklist, $this->ignoredIds));

                if (!empty($this->toBlacklist)) {
                    BlacklistJob::dispatch($this->toBlacklist, $this->apiUsername, $this->apiPassword);
                }

                if (!empty($this->toUnblacklist)) {
                    UnblacklistJob::dispatch($this->toUnblacklist, $this->apiUsername, $this->apiPassword);
                }

                RestartJob::dispatch($this->apiUsername, $this->apiPassword);
            },
        ];
    }

    public function chunkSize(): int
    {
        return 500; // adjust based on memory and file size
    }
}
