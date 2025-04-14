<?php

namespace App\Jobs;

use App\Services\EventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * KonurEventJob
 */
class EventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $service;

    public function __construct(
        protected array $data
    ) {
        $this->service = new EventService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->service->proccess();
    }
}
