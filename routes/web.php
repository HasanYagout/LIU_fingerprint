<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('admin');
});


Route::get('/test', function () {
    $timezone = 'Asia/Aden';
    $targetHour = 6;

    while (true) {
        $now = Carbon::now($timezone);
        $targetTime = $now->copy()->setTime($targetHour, 0, 0);

        // If it's already past 6:00 AM today, set target for tomorrow
        if ($now->greaterThan($targetTime)) {
            $targetTime->addDay();
        }

        $secondsUntilTarget = 60;
        $this->info('Current time: '.$now->format('Y-m-d H:i:s'));
        $this->info('Next run at: '.$targetTime->format('Y-m-d H:i:s'));
        $this->info('Sleeping for '.$secondsUntilTarget.' seconds...');

        sleep($secondsUntilTarget);

        $this->info('Running scheduled tasks at '.Carbon::now($timezone)->format('Y-m-d H:i:s'));
        Artisan::call('students:update-status');

        // Sleep for 1 day minus 1 second to prevent immediate re-run
        sleep(60);
    }
});


