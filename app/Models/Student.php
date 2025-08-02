<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function exceptions(): HasMany
    {
        return $this->hasMany(StudentException::class, 'student_id', 'student_id');
    }

    public function semesters()
    {
        return $this->belongsToMany(Semester::class, 'semester_student', 'student_id', 'semester_id')
            ->withPivot('percentage')
            ->withTimestamps();
    }

}
