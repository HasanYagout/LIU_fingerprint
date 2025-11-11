<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'year',
        'start_date',
        'midterm_date',
        'end_date',
    ];
    protected $casts = [
        'start_date'   => 'date',
        'midterm_date' => 'date',
        'end_date'     => 'date',
    ];

    protected $dates = [
        'start_date',
        'midterm_date',
        'end_date',
    ];


    public function students()
    {
        return $this->belongsToMany(Student::class, 'semester_student', 'semester_id', 'student_id')
            ->withPivot('percentage')
            ->withTimestamps();
    }

}
