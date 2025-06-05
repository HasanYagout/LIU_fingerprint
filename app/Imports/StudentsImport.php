<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        return Student::updateOrCreate([
            'student_id'     => $row['student_id'],
            'name'      => $row['name'],
            'major'          => $row['major'],
            'percentage' => $row['percentage'],
        ]);
    }
}
