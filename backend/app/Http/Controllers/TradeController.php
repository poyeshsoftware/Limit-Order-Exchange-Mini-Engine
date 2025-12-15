<?php

namespace App\Http\Controllers;

use App\Http\Resources\TradeResource;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TradeController extends Controller
{
    public function index(Request $request): array
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', Rule::in(['BTC', 'ETH'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

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
