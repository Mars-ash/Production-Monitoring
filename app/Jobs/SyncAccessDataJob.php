<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncAccessDataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly ?string $date = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\AccessSyncService $syncService): void
    {
        $syncService->sync($this->date);
    }
}
