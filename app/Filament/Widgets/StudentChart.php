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

    protected static ?string $pollingInterval = '10s';

    /**
     * Holds the state of the filter form.
     */
    public ?array $filterData;

    /**
     * This widget is only visible to users with the 'Manager' role.
     */
    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->hasRole('Manager');
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
        // Get start and end dates from the public filterData property.
        $startDate = $this->filterData['startDate'];
        $endDate = $this->filterData['endDate'];

        // Fetch all data from the Sushi model.
        // Then, filter the collection by the selected date range.
        $stats = AttendanceStat::all()->whereBetween('date', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);

        // Sum the values directly from the model's attributes.
        $totalUnpaidUsers = $stats->sum('unique_not_paid_users');
        $totalEnteredUsers = $stats->sum('unique_entered_users');

        // Calculate the number of paid users.
        $totalPaidUsers = $totalEnteredUsers - $totalUnpaidUsers;

        // Return the data in the format the pie chart widget expects.
        return [
            'datasets' => [
                [
                    'label' => 'Student Entries',
                    'data' => [$totalPaidUsers, $totalUnpaidUsers],
                    'backgroundColor' => [
                        'hsl(208, 88%, 57%)', // Blue for Paid
                        'hsl(348, 83%, 47%)', // Red for Unpaid
                    ],
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
