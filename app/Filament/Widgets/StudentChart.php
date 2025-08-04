<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceStat; // Import your AttendanceStat model
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;

class StudentChart extends ChartWidget
{
    use InteractsWithPageFilters;
    protected static ?string $heading = 'Student Entry Status';

    protected static ?int $sort=4;
    /**
     * Holds the state of the filter form.
     */
    public ?array $filterData;

    /**
     * This widget is only visible to users with the 'Manager' role.
     */
    public static function canView(): bool
    {
        return auth()->user() && !auth()->user()->hasRole('Accountant');
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
        $filters = $this->filters;

        $startDate = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->endOfMonth()->format('Y-m-d');
        $start = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $stats = AttendanceStat::query()
            ->whereBetween('date', [$start, $end])
            ->get();
        $totalUnpaidUsers = $stats->sum('unique_not_paid_users');
        $totalEnteredUsers = $stats->sum('unique_entered_users');


        return [
            'datasets' => [
                [
                    'label' => 'Student Entries',
                    'data' => [$totalEnteredUsers, $totalUnpaidUsers],

                    // Light background shades
                    'backgroundColor' => [
                        'hsl(133, 88%, 90%)', // Light blue
                        'hsl(348, 83%, 90%)', // Light red
                    ],

                    // Vivid borders
                    'borderColor' => [
                        'hsl(133, 88%, 60%)',
                        'hsl(348, 83%, 60%)',
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
