<?php

namespace App\Filament\Resources\StudentExceptionResource\Pages;

use App\Filament\Resources\StudentExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStudentException extends CreateRecord
{
    protected static string $resource = StudentExceptionResource::class;

    protected function handleRecordCreation(array $data): Model
    {

        // Create the StudentException record with all the data
        $record = static::getModel()::create([
            'student_id' => $data['student_id'],
            'semester_id' => $data['semester_id'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'reason' => $data['reason'] ?? null,
        ]);

        return $record;
    }
}
