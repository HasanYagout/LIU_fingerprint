<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentException extends Model
{
    protected $fillable = ['student_id','semester_id','from_date','to_date','reason'];
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
