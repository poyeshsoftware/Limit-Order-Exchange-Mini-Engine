<?php

namespace App\Http\Controllers;

use App\Http\Requests\TradeIndexRequest;
use App\Http\Resources\TradeResource;
use App\Models\Trade;

class TradeController extends Controller
{
    public function index(TradeIndexRequest $request): array
    {
        $validated = $request->validated();

        $limit = $validated['limit'] ?? 50;

        $trades = Trade::query()
            ->where('symbol', $validated['symbol'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get([
                'id',
                'symbol',
                'buy_order_id',
                'sell_order_id',
                'price',
                'amount',
                'usd_volume',
                'fee_usd',
                'created_at',
            ]);

        return [
            'trades' => TradeResource::collection($trades)->resolve(),
        ];
    }
}
