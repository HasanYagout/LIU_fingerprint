<?php
namespace App\Filament\Widgets;
//
//use App\Models\AttendanceStat;
//use Illuminate\Database\Eloquent\Model;
//use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
//use Saade\FilamentFullCalendar\Actions\ViewAction;
//use Filament\Actions\Action;
//
//class CalendarWidget extends FullCalendarWidget
//{
//    public Model | string | null $model = AttendanceStat::class;
//
//    public function fetchEvents(array $fetchInfo): array
//    {
//        // Since getRows() always gives full API response, you can filter here if needed
//
//        return AttendanceStat::all()->map(function (AttendanceStat $event) {
//            return [
//                'id'    => $event->id,
//                'title' => $event->title,
//                'start' => $event->start,
//                'end'   => $event->end,
//            ];
//        })->toArray();
//    }
//
//    public function headerActions(): array
//    {
//        return [];
//    }
//    protected function modalActions(): array
//    {
//        return [
//
//        ];
//    }
//
//    protected function viewAction(): Action
//    {
//        // Return a disabled action to hide/remove it
//        return Action::make('view')->disabled();
//    }
//    public static function canView(): bool
//    {
//        return true;
//    }
//}
use App\Models\AttendanceStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Actions;


class CalendarWidget extends FullCalendarWidget
{
    public string|null|\Illuminate\Database\Eloquent\Model $model = AttendanceStat::class;
    protected static ?int $sort=2;

    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start'])->format('Y-m-d');
        $end = Carbon::parse($fetchInfo['end'])->format('Y-m-d');
        return AttendanceStat::query()
            ->whereBetween('start', [$start, $end])
            ->get()
            ->map(function (AttendanceStat $event) {
                return [
                    'id' => $event->id,
                    'title' => "Entries: {$event->unique_entered_users} (Unpaid: {$event->unique_not_paid_users})",
                    'start' => $event->start,
                    'end' => $event->end,
                    'extendedProps' => [
                        'unique_entries' => $event->unique_entries,
                        'unique_entered_users' => $event->unique_entered_users,
                        'unique_not_paid_users' => $event->unique_not_paid_users,
                    ],
                    // Optional: add color coding based on some condition
                    'color' => $event->unique_not_paid_users > 0 ? '#f87171' : '#34d399',
                ];
            })
            ->toArray();
    }

    protected function modalActions(): array
    {
        return []; // No modals
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
