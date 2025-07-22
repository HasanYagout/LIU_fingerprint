<?php
// app/Services/PaymentAuthorizationService.php

namespace App\Helpers;

use App\Models\Semester;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class PaymentAuthorizationService
{
    /**
     * Returns true if $student has paid enough percentage
     * for the given $semester based on the current date.
     *
     * Optionally, pass $dateForCheck if you want to “simulate”
     * a different date than today.
     */
    public function canAccess(Student $student, Semester $semester, Carbon $dateForCheck = null): bool
    {
        $dateForCheck = $dateForCheck ?? Carbon::now();

        // 1) Determine which phase we’re in
        if ($dateForCheck->lt($semester->start_date) || $dateForCheck->gt($semester->end_date)) {
            // If it’s entirely before the semester starts or after it ends,
            // you might decide “no access” or “always allow.” Here, we’ll deny if outside.
            return false;
        }

        // Are we in “start” or “midterm”?
        if ($dateForCheck->lt($semester->midterm_date)) {
            $phaseKey = 'start';
        } else {
            $phaseKey = 'midterm';
        }

        // 2) Look up the student’s pivot record for this semester
        $pivot = $student->semesters()
            ->where('semester_id', $semester->id)
            ->first()?->pivot;

        if (! $pivot) {
            // No payment info entered yet
            return false;
        }

        $paidPercentage = (int) $pivot->percentage;

        // 3) Load the required threshold from config/payment_thresholds.php
        $thresholds = Config::get('payment_thresholds', [
            'start'   => 60,
            'midterm' => 50,
            'outside' => 0,
        ]);

        $required = $thresholds[$phaseKey] ?? 0;

        // 4) Grant or deny
        return $paidPercentage >= $required;
    }
}
