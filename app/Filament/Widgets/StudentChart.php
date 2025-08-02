<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceStat; // Import your AttendanceStat model
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;

class StudentChart extends ChartWidget
{
    protected static ?string $heading = 'Student Entry Status';


    /**
     * Holds the state of the filter form.
     */
    public ?array $filterData;

    /**
     * This widget is only visible to users with the 'Manager' role.
     */
    public static function canView(): bool
    {
        return auth()->user() && !auth()->user()->hasRole('accountant');
    }

    /**
     * Initializes the component and sets default filter values.
     */
    public function mount(): void
    {
        $this->filterData = [
            'startDate' => '2025-07-01',
            'endDate' => '2025-07-31',
        ];
    }

    /**
     * Defines the filter form schema for the chart.
     * This form's state is bound to the public $filterData property.
     */
    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('filterData.startDate')
                ->label('Start Date'),
            DatePicker::make('filterData.endDate')
                ->label('End Date'),
        ];
    }

    protected function getData(): array
    {
        $startDate = $this->filterData['startDate'];
        $endDate = $this->filterData['endDate'];

        $stats = AttendanceStat::all()->whereBetween('date', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);

        $totalUnpaidUsers = $stats->sum('unique_not_paid_users');
        $totalEnteredUsers = $stats->sum('unique_entered_users');
        $totalPaidUsers = $totalEnteredUsers - $totalUnpaidUsers;

        return [
            'datasets' => [
                [
                    'label' => 'Student Entries',
                    'data' => [$totalPaidUsers, $totalUnpaidUsers],

                    // Light background shades
                    'backgroundColor' => [
                        'hsl(208, 88%, 85%)', // Light blue
                        'hsl(348, 83%, 85%)', // Light red
                    ],

                    // Vivid borders
                    'borderColor' => [
                        'hsl(208, 88%, 45%)', // Darker vivid blue
                        'hsl(348, 83%, 45%)', // Darker vivid red
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Paid Users', 'Unpaid Users'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
