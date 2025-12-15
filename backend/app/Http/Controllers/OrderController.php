<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\CreateOrderAction;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function myOrders(Request $request): array
    {
        $validated = $request->validate([
            'symbol' => ['sometimes', 'string', Rule::in(['BTC', 'ETH'])],
        ]);

        $query = $request->user()
            ->orders()
            ->orderBy('created_at', 'desc');

        if (isset($validated['symbol'])) {
            $query->where('symbol', $validated['symbol']);
        }

        return [
            'orders' => $query->get(['id', 'symbol', 'side', 'price', 'amount', 'status', 'created_at']),
        ];
    }

    public function index(Request $request): array
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', Rule::in(['BTC', 'ETH'])],
        ]);

        $symbol = $validated['symbol'];

        $buy = Order::query()
            ->where('symbol', $symbol)
            ->where('status', Order::STATUS_OPEN)
            ->where('side', Order::SIDE_BUY)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'price', 'amount', 'created_at']);

        $sell = Order::query()
            ->where('symbol', $symbol)
            ->where('status', Order::STATUS_OPEN)
            ->where('side', Order::SIDE_SELL)
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'price', 'amount', 'created_at']);

        return [
            'buy' => $buy,
            'sell' => $sell,
        ];
    }

    public function store(StoreOrderRequest $request, CreateOrderAction $createOrderAction)
    {
        $data = $request->validated();

        $order = $createOrderAction->execute(
            user: $request->user(),
            symbol: $data['symbol'],
            side: $data['side'],
            price: (string) $data['price'],
            amount: (string) $data['amount'],
        );

        return response()->json([
            'id' => $order->id,
            'symbol' => $order->symbol,
            'side' => $order->side,
            'price' => $order->price,
            'amount' => $order->amount,
            'status' => $order->status,
            'created_at' => $order->created_at,
        ], 201);
    }

    public function cancel(Request $request, Order $order, CancelOrderAction $cancelOrderAction): array
    {
        $cancelled = $cancelOrderAction->execute($request->user(), $order);

        return [
            'id' => $cancelled->id,
            'status' => $cancelled->status,
        ];
    }
}
