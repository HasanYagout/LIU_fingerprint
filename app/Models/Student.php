<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = 'student_id'; // If 'student_id' is the PK
    public $incrementing = false;
    protected $fillable = [
        'name',
        'student_id',
        'major',
        'percentage'
        ];


    public function semesters()
    {
        return $this->belongsToMany(Semester::class, 'semester_student', 'student_id', 'semester_id')
            ->withPivot('percentage')
            ->withTimestamps();
    }

}
