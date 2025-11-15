<?php

namespace App\Services;

use App\Models\AttendanceStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceStatCache
{
    protected static ?Collection $cachedData = null;
    protected static ?Collection $cachedAll = null;

    public static function get(Carbon $start, Carbon $end): Collection
    {

        // If cached already, reuse it
        if (static::$cachedData !== null) {
            return static::$cachedData;
        }

        // Otherwise, fetch from the model once
        AttendanceStat::setDateRange($start->format('Ymd'), $end->format('Ymd'));
        $data = collect((new AttendanceStat())->getRows());

        // Cache for the current request
        static::$cachedData = $data;

        return $data;
    }


    public static function clear(): void
    {
        static::$cachedData = null;
    }
}
