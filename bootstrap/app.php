<?php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('students:update-statuses')
            ->dailyAt('6:00')
            ->timezone('Asia/Aden')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/laravel.log'));

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
