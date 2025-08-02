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

class BlacklistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $studentIds,
        protected string $username,
        protected string $password
    ) {}

    public function handle()
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post('http://172.170.17.5:2001/api/v1/blacklist', [
                    'studentIds' => $this->studentIds, // Changed to studentids
                    'timestamp' => Carbon::now()->toDateTimeString()
                ]);

            Log::info("Blacklist processed", [
                'count' => count($this->studentIds),
                'request_payload' => ['studentids' => $this->studentIds],
                'response' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error("Blacklist failed", [
                'error' => $e->getMessage(),
                'studentids' => $this->studentIds
            ]);
            $this->release(30);
        }
    }
}
