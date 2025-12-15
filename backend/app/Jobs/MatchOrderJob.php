<?php

namespace App\Jobs;

use App\Actions\Matching\MatchOrderAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchOrderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $orderId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(MatchOrderAction $matchOrderAction): void
    {
        $matchOrderAction->execute($this->orderId);
    }
}
