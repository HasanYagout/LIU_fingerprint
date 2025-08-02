<?php
// app/Console/Commands/UpdateStudentStatusesCommand.php

namespace App\Console\Commands;

use App\Jobs\CheckStudentExceptions;
use App\Jobs\UpdateStudentStatuses;
use Illuminate\Console\Command;

class UpdateStudentStatusesCommand extends Command
{
    protected $signature = 'students:update-statuses';
    protected $description = 'Update student statuses based on active exceptions';


    public function handle()
    {
        $this->info('Dispatching student status update job...');
        CheckStudentExceptions::dispatch();
        $this->info('Job dispatched successfully!');
    }
}
