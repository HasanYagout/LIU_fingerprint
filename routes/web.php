<?php
use App\Http\Controllers\AttendanceController;
use App\Models\Student;
use App\Models\StudentException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Models\AttendanceLog;
use App\Jobs\BlacklistJob;
use App\Jobs\UnblacklistJob;
use App\Jobs\RestartJob;
use App\Models\Semester;

Route::get('/', function () {
    return redirect('admin');
});

