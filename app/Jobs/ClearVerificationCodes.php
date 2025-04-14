<?php

namespace App\Jobs;

use App\Contracts\AuthServiceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClearVerificationCodes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected AuthServiceContract $authService)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->authService->clearVerificationCodes();
    }
}
