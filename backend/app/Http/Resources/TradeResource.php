<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'buy_order_id' => $this->buy_order_id,
            'sell_order_id' => $this->sell_order_id,
            'price' => $this->price,
            'amount' => $this->amount,
            'usd_volume' => $this->usd_volume,
            'fee_usd' => $this->fee_usd,
            'created_at' => $this->created_at,
        ];
    }
}

