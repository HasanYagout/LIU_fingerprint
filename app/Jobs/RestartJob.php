<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RestartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $username,
        protected string $password
    ) {}

    public function handle()
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post('http://172.170.17.5:2001/api/v1/restart-services');

            Log::info("Service restart completed");
        } catch (\Exception $e) {
            Log::error("Restart failed", [
                'error' => $e->getMessage()
            ]);
            $this->release(60); // Retry after 60 seconds
        }
    }
}
