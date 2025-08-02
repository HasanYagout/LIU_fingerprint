<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemesterStudent extends Model
{
    protected $table = 'semester_student';

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // Define the relationship to Semester
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}
