<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MyCalendarWidget extends CalendarWidget
{
    protected static ?int $sort = 1;

    /**
     * @throws ConnectionException
     */
    protected bool $eventClickEnabled = true;

    protected function config(): array
    {
        return [
            'eventClick' => true,  // ✅ enable click handling
        ];
    }
    protected function getEvents(FetchInfo $info): Collection
    {
        $response = Http::withBasicAuth(
            config('services.api.username'),
            config('services.api.password'),
        )->post('http://127.0.0.1:8001/api/stat');
        $data = $response->json();
        $dailyStats = collect($data['dailyStats'] ?? []);

        return $dailyStats->map(function ($day) {

            $date = Carbon::createFromFormat('Ymd', $day['date'])->toDateString();

            return CalendarEvent::make()
                ->title("Entries: {$day['uniqueEntries']}")
                ->start($date)
                ->end($date)
                ->allDay(true)
                ->backgroundColor('#3B82F6')

                // ✅ Add event click URL
                ->url(route('filament.admin.pages.attendance-logs', ['date' => $date]))
            ->extendedProp('uniqueEntries', $day['uniqueEntries'])
                ->extendedProp('uniqueEnteredUsers', $day['uniqueEnteredUsers'])
                ->extendedProp('uniqueNotPaidUsers', $day['uniqueNotPaidUsers']);
        });
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
}
