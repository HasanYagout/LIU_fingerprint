<?php
// app/Helpers/Helpers.php

namespace App\Helpers;

use App\Models\Student;
use App\Models\Semester;
use Carbon\Carbon;

class Helpers
{
    /**
     * @param  int          $studentCode    students.student_id
     * @param  int          $semesterId     semesters.id
     * @param  Carbon|null  $dateForCheck   override “today”
     * @return array{percentage:int, required:int, color:string}
     */
    public static function getPaymentStatus(int $studentCode, int $semesterId, ?Carbon $dateForCheck = null): array
    {
        $today = ($dateForCheck ?: Carbon::now())->startOfDay();

        $student  = Student::where('student_id', $studentCode)->firstOrFail();
        $semester = Semester::findOrFail($semesterId);

        // 1) Define your thresholds (you can move these to config/payment_thresholds.php)
        $thresholds = [
            'start'   => 40,   // before midterm window
            'midterm' => 50,   // two weeks either side of midterm
            'final'   => 100,  // two weeks before final
        ];

        // 2) Compute the date windows
        $midtermWindowStart = Carbon::parse($semester->midterm_date)
            ->copy()
            ->subWeeks(2);

        $finalWindowStart   = Carbon::parse($semester->end_date)
            ->copy()
            ->subWeeks(2);

// 3) Pick the required threshold based on which cutoff we've crossed:
        if ($today->gte($finalWindowStart)) {
            // 🔴 We're within two weeks before final (or after): require final fees
            $required = $thresholds['final'];
        } elseif ($today->gte($midtermWindowStart)) {
            // 🟡 We're within two weeks before midterm (but before final window): require midterm fees
            $required = $thresholds['midterm'];
        } else {
            // 🟢 We're still more than two weeks away from midterm: require start-of-term fees
            $required = $thresholds['start'];
        }

        // 4) Fetch the student’s paid percentage from the pivot
        $pivot = $student
            ->semesters()
            ->wherePivot('semester_id', $semester->id)
            ->first()?->pivot;

        $paid = $pivot ? (int) $pivot->percentage : 0;

        // 5) Decide badge color: green if met, red otherwise
        $color = ($paid >= $required) ? 'success' : 'danger';
        return [
            'percentage' => $paid,
            'required'   => $required,
            'color'      => $color,
        ];
    }
    public static function getRequiredPercentage(int $semester): int
    {
        $semester=Semester::where('status',1)->find($semester);
        $now = Carbon::now();
        $startDate = Carbon::parse($semester->start_date);
        $midtermDate = Carbon::parse($semester->midterm_date);
        $endDate = Carbon::parse($semester->end_date);

        // Two weeks before midterm (midterm threshold)
        $midtermThreshold = $midtermDate->copy()->subWeeks(2);
        // Two weeks before final (final threshold)
        $finalThreshold = $endDate->copy()->subWeeks(2);

        if ($now->gte($finalThreshold)) {
            // After final threshold (two weeks before final) - require 100%
            return 100;
        } elseif ($now->gte($midtermThreshold)) {
            // After midterm threshold (two weeks before midterm) but before final threshold - require 50%
            return 50;
        } else {
            // Before midterm threshold - require 0%
            return 0;
        }
    }

}
