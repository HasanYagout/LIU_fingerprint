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
use Illuminate\Support\HtmlString;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Actions;


class CalendarWidget extends FullCalendarWidget
{
    public string|null|\Illuminate\Database\Eloquent\Model $model = AttendanceStat::class;
    protected static ?int $sort=2;

    public function fetchEvents(array $fetchInfo): array
    {
        return AttendanceStat::all()->map(function (AttendanceStat $event) {
            return [
                'id'    => $event->id,
                'title' => $event->title,
                'start' => $event->start,
                'end'   => $event->end,
            ];
        })->toArray();
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
