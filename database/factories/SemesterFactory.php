<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SemesterFactory extends Factory
{
    public function definition(): array
    {
        // Get all existing years and semester types
        $existingSemesters = Semester::all()
            ->groupBy('year')
            ->map(fn($group) => $group->pluck('name')->map(fn($name) => strtolower(explode(' ', $name)[0])));

        // Find a year that doesn't already have all 3 semester types
        $year = $this->findAvailableYear($existingSemesters);

        // Get available semester types for this year
        $availableTypes = $this->getAvailableTypes($existingSemesters, $year);
        $semesterType = $this->faker->randomElement($availableTypes);

        $dates = $this->getSemesterDates($year, $semesterType);

        return [
            'name' => $semesterType,
            'year' => $year,
            'start_date' => $dates['start_date'],
            'midterm_date' => $dates['midterm_date'],
            'end_date' => $dates['end_date'],
        ];
    }

    protected function findAvailableYear($existingSemesters): int
    {
        $year = $this->faker->numberBetween(2020, 2025);

        // If year exists and already has 3 semesters, find another year
        while (isset($existingSemesters[$year])) {
            if ($existingSemesters[$year]->count() >= 3) {
                $year = $this->faker->numberBetween(2020, 2025);
            } else {
                break;
            }
        }

        return $year;
    }

    protected function getAvailableTypes($existingSemesters, int $year): array
    {
        $allTypes = ['fall', 'spring', 'summer'];

        if (!isset($existingSemesters[$year])) {
            return $allTypes;
        }

        return array_diff($allTypes, $existingSemesters[$year]->toArray());
    }

    protected function getSemesterDates(int $year, string $type): array
    {
        return match($type) {
            'fall' => [
                'start_date' => Carbon::create($year, 8, 15),
                'midterm_date' => Carbon::create($year, 10, 15),
                'end_date' => Carbon::create($year, 12, 15),
            ],
            'spring' => [
                'start_date' => Carbon::create($year, 1, 10),
                'midterm_date' => Carbon::create($year, 3, 1),
                'end_date' => Carbon::create($year, 5, 15),
            ],
            'summer' => [
                'start_date' => Carbon::create($year, 6, 1),
                'midterm_date' => Carbon::create($year, 7, 1),
                'end_date' => Carbon::create($year, 8, 1),
            ],
        };
    }
}
