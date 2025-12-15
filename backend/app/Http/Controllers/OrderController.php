<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\CreateOrderAction;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\MyOrdersRequest;
use App\Http\Requests\OrderBookRequest;
use App\Http\Resources\OrderBookOrderResource;
use App\Http\Resources\OrderResource;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;

class OrderController extends Controller
{
    public function myOrders(MyOrdersRequest $request): array
    {
        $validated = $request->validated();

        $query = $request->user()
            ->orders()
            ->latestFirst();

        if (isset($validated['symbol'])) {
            $query->forSymbol($validated['symbol']);
        }

        $orders = $query->get(['id', 'symbol', 'side', 'price', 'amount', 'status', 'created_at']);

        return [
            'orders' => OrderResource::collection($orders)->resolve(),
        ];
    }

    public function index(OrderBookRequest $request): array
    {
        $symbol = $request->validated()['symbol'];

        $buy = Order::query()
            ->openBook($symbol, Order::SIDE_BUY)
            ->get(['id', 'price', 'amount', 'created_at']);

        $sell = Order::query()
            ->openBook($symbol, Order::SIDE_SELL)
            ->get(['id', 'price', 'amount', 'created_at']);

        return [
            'buy' => OrderBookOrderResource::collection($buy)->resolve(),
            'sell' => OrderBookOrderResource::collection($sell)->resolve(),
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

    public function cancel(CancelOrderRequest $request, Order $order, CancelOrderAction $cancelOrderAction): array
    {
        $cancelled = $cancelOrderAction->execute($request->user(), $order);

        return [
            'id' => $cancelled->id,
            'status' => $cancelled->status,
        ];
    }
}
