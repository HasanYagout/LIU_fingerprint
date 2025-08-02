<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Helpers\Helpers;
use App\Models\Semester;
use App\Models\Student;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
    protected array $toBlacklist = [];
    protected array $toUnblacklist = [];

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data) {
                $studentId = (int)$data['id'];
                $percentage = (int)($data['percentage'] ?? 0);
                $student = Student::updateOrCreate(
                    ['student_id' => $studentId],
                    [
                        'name' => $data['name'],
                        'major' => $data['major'] ?? null,
                    ]
                );

                // Determine required percentage based on semester dates
                $requiredPercentage = Helpers::getRequiredPercentage($data['semester_id']);

                $semester = Semester::findOrFail($data['semester_id']);
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
                'student_id' => $data['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw the exception
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
