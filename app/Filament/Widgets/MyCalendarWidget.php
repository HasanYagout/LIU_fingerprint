<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceStat;
use App\Services\AttendanceStatCache;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MyCalendarWidget extends CalendarWidget
{
    use InteractsWithPageFilters;
    use HasWidgetShield;
    protected static ?int $sort = 2;

    /**
     * @throws ConnectionException
     */
    protected bool $eventClickEnabled = true;

    protected function config(): array
    {
        return [
            'eventClick' => true,
        ];
    }

    protected function getEvents(FetchInfo $info): Collection
    {
        // Get filters
        $startInput = $this->filters['startDate'] ?? now();
        $endInput = $this->filters['endDate'] ?? now();

        // Parse to Carbon objects for filtering
        $start = Carbon::parse($startInput)->startOfDay();
        $end = Carbon::parse($endInput)->endOfDay();
        $dailyStats = AttendanceStatCache::get($start, $end);

        return $dailyStats->map(function ($day) {
            return CalendarEvent::make()
                ->title("Entries: {$day['unique_entered_users']} (Unpaid: {$day['unique_not_paid_users']})")
                ->start($day['start'])
                ->end($day['end'])
                ->allDay(true)
                ->backgroundColor('#3B82F6')
                ->url(route('filament.admin.pages.attendance-logs', ['date' => $day['start']]))
                ->extendedProp('uniqueEntries', $day['unique_entries'])
                ->extendedProp('uniqueEnteredUsers', $day['unique_entered_users'])
                ->extendedProp('uniqueNotPaidUsers', $day['unique_not_paid_users']);
        });
    }

    // Add this method to automatically refresh when filters change
    public static function shouldRefreshOnPageFilter(): bool
    {
        return true;
    }

    protected function headerFormats(): array
    {
        return [
            'month' => 'MMMM YYYY',
            'week' => 'MMM D',
            'day' => 'dddd, MMM D, YYYY',
        ];
    }

    protected function initialView(): string
    {
        return 'month';
    }

    protected function views(): array
    {
        return ['month', 'week', 'day'];
    }

    protected function firstDayOfWeek(): int
    {
        return 1; // Monday
    }

    protected function calendarHeight(): string
    {
        return '600px';
    }

    protected function getListeners(): array
    {
        return [
            'updateCalendar' => 'refreshCalendar',
        ];
    }

    public function refreshCalendar(): void
    {
        $this->refreshRecords();
    }
}
