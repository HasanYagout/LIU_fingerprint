<?php
namespace App\Filament\Widgets;
use App\Models\AttendanceStat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Actions;


class CalendarWidget extends FullCalendarWidget
{
    use InteractsWithPageFilters;
    public string|null|\Illuminate\Database\Eloquent\Model $model = AttendanceStat::class;
    protected static ?int $sort=2;

    public function fetchEvents(array $fetchInfo): array
    {
        // Get filters
        $startInput = $this->filters['startDate'] ?? now()->toDateString();
        $endInput = $this->filters['endDate'] ?? now()->toDateString();

        // Parse to Carbon objects for filtering
        $start = Carbon::parse($startInput)->startOfDay();
        $end = Carbon::parse($endInput)->endOfDay();

        // Set the date range so Sushi reboots and fetches fresh API data
        AttendanceStat::setDateRange($startInput, $endInput);

        // Get all in-memory rows from Sushi
        return collect(AttendanceStat::all())
            ->filter(function ($event) use ($start, $end) {
                $eventDate = Carbon::parse($event['start']);
                return $eventDate->between($start, $end);
            })
            ->map(function ($event) {
                return [
                    'id' => $event['id'],
                    'title' => "Entries: {$event['unique_entered_users']} (Unpaid: {$event['unique_not_paid_users']})",
                    'start' => $event['start'],
                    'end' => $event['end'],
                    'extendedProps' => [
                        'unique_entries' => $event['unique_entries'],
                        'unique_entered_users' => $event['unique_entered_users'],
                        'unique_not_paid_users' => $event['unique_not_paid_users'],
                    ],
                    'color' => $event['unique_not_paid_users'] > 0 ? '#f87171' : '#34d399',
                ];
            })
            ->toArray();
    }


    protected function modalActions(): array
    {
        return []; // No modals
    }
    protected function getViewData(): array
    {
        // Get filters
        $filters = $this->filters;

        $startDate = $filters['startDate'] ?? now()->format('Y-m-d');
        $endDate = $filters['endDate'] ?? now()->format('Y-m-d');

        // Pass them to the model
        AttendanceStat::setDateRange($startDate, $endDate);

        return [
            'events' => AttendanceStat::all(),
        ];
    }
    public function getEvents(): array
    {
        // This will be called when filters change
        $startDate = $this->filters['startDate'] ?? now()->subMonth()->format('Y-m-d');
        $endDate = $this->filters['endDate'] ?? now()->addMonth()->format('Y-m-d');

        AttendanceStat::setDateRange($startDate, $endDate);
        AttendanceStat::clearBootedModels();

        return $this->fetchEvents([
            'start' => $startDate,
            'end' => $endDate,
        ]);
    }

    public function onEventClick(array $event): void
    {
        $date = \Carbon\Carbon::parse($event['start'])->format('Y-m-d');
        // Optionally: you can pass student ID if available in event['extendedProps']
        $studentId = $event['extendedProps']['student_id'] ?? null;

        $params = ['date' => $date];

        if ($studentId) {
            $params['studentId'] = $studentId;
        }

        $this->redirect(route('filament.admin.pages.attendance-log-search', $params));
    }


    protected function headerActions(): array
    {
        return [];
    }


    public static function canView(): bool
    {
        return true;
    }
}
