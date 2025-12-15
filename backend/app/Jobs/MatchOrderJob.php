<?php

namespace App\Jobs;

use App\Actions\Matching\MatchOrderAction;
use App\Events\OrderMatched;
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
        $match = $matchOrderAction->execute($this->orderId);

        if (!$match) {
            return;
        }

        event(new OrderMatched(
            symbol: $match['symbol'],
            buyOrderId: $match['buy_order_id'],
            sellOrderId: $match['sell_order_id'],
            buyerId: $match['buyer_id'],
            sellerId: $match['seller_id'],
        ));
    }
}
