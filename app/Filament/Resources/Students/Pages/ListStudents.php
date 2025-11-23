<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Imports\ExceptionsImport;
use App\Imports\StudentsImport;
use App\Jobs\BlacklistJob;
use App\Jobs\RestartJob;
use App\Jobs\UnblacklistJob;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentException;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import Students')
                ->label('Import Students')
                ->color('primary')
                ->schema([
                    Select::make('semester_id')
                        ->label('Semester')
                        ->options(Semester::where('status',1)->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->required(),
                    FileUpload::make('file')
                        ->label('Excel / CSV File')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                        ]),
                ])
                ->action(function (array $data): void {
                    $semesterId = $data['semester_id'];
                    $relativePath = $data['file'];
                    $fullPath = Storage::disk('local')->path($relativePath);

                    Excel::import(new StudentsImport($semesterId), $fullPath);

                    Notification::make()
                        ->title('Students imported successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->size('lg'),
            Action::make('import Exceptions')
                ->label('Import Exceptions')
                ->color('primary')
                ->schema([
                    Select::make('semester_id')
                        ->label('Semester')
                        ->options(Semester::where('status',1)->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->required(),
                    FileUpload::make('file')
                        ->label('Excel / CSV File')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                        ]),
                ])
                ->action(function (array $data): void {
                    $semesterId = $data['semester_id'];
                    $relativePath = $data['file'];
                    $fullPath = Storage::disk('local')->path($relativePath);

                    Excel::import(new ExceptionsImport($semesterId), $fullPath);

                    Notification::make()
                        ->title('Students imported successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->size('lg'),
            Action::make('runFunction')
                ->label('Refresh Status')
                ->color('primary')
                ->action(function () {
                    $cacheKey = 'refresh_status_cooldown';

                    // Check cooldown
                    if (cache()->has($cacheKey)) {
                        $seconds = cache()->get($cacheKey) - time();
                        Notification::make()
                            ->title("Please wait {$seconds} seconds before refreshing again.")
                            ->warning()
                            ->send();
                        return;
                    }

                    // Run your function
                    $this->myCustomFunction();

                    // Set cooldown (5 min)
                    cache()->put($cacheKey, time() + 300, now()->addMinutes(5));

                    Notification::make()
                        ->title("Refresh started. Please wait up to 5 minutes.")
                        ->success()
                        ->send();
                })
                ->disabled(fn () => cache()->has('refresh_status_cooldown'))
                ->tooltip(function () {
                    if (! cache()->has('refresh_status_cooldown')) {
                        return null;
                    }

                    $seconds = cache()->get('refresh_status_cooldown') - time();

                    if ($seconds < 0) {
                        return null;
                    }

                    $minutes = floor($seconds / 60);
                    $remainingSeconds = $seconds % 60;

                    return "Try again after {$minutes}m {$remainingSeconds}s.";
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
