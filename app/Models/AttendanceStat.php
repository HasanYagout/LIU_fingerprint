<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class AttendanceStat extends Model
{
    use Sushi;

    /**
     * Define the data structure and how to cast the types.
     */
    protected $casts = [
        'date' => 'date',
        'unique_entered_users' => 'integer',
        'unique_not_paid_users' => 'integer',
    ];

    /**
     * Provides static demo data instead of fetching from an API.
     */
    public function getRows(): array
    {
        // Static demo data matching the structure of your API response.
        $demoStats = [
            [
                'date' => '20250822',
                'uniqueEnteredUsers' => 4,
                'uniqueNotPaidUsers' => 2,
            ],
            [
                'date' => '20250725',
                'uniqueEnteredUsers' => 5,
                'uniqueNotPaidUsers' => 5,
            ],
            [
                'date' => '20250627',
                'uniqueEnteredUsers' => 6,
                'uniqueNotPaidUsers' => 4,
            ],
        ];

        // Map over the demo stats to format them for the Sushi model,
        // just as the original API call did.
        return collect($demoStats)->map(function ($stat) {
            return [
                'date' => Carbon::createFromFormat('Ymd', $stat['date'])->toDateString(),
                'unique_entered_users' => $stat['uniqueEnteredUsers'],
                'unique_not_paid_users' => $stat['uniqueNotPaidUsers'],
            ];
        })->toArray();
    }
}
