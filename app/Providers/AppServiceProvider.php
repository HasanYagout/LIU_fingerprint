<?php

namespace App\Providers;

use App\Jobs\CheckSemesterPeriodJob;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LogViewer::auth(function ($request) {
            // Only allow authenticated users with 'admin' role
            return auth()->check() && auth()->user()->hasRole('admin');
        });
        FilamentAsset::register([
            Css::make('custom-stylesheet', __DIR__ . '/../../resources/css/filament-theme.css'),
        ]);

    }

}
