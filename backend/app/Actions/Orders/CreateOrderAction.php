<?php

namespace App\Actions\Orders;

use App\Jobs\MatchOrderJob;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateOrderAction
{
    public function execute(User $user, string $symbol, string $side, string $price, string $amount): Order
    {
        $symbol = strtoupper($symbol);
        $side = strtolower($side);

        return DB::transaction(function () use ($user, $symbol, $side, $price, $amount): Order {
            if ($side === Order::SIDE_BUY) {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $cost = Decimal::mul($amount, $price);

                if (Decimal::cmp($lockedUser->balance, $cost) < 0) {
                    throw ValidationException::withMessages([
                        'balance' => 'Insufficient USD balance.',
                    ]);
                }

                $lockedUser->balance = Decimal::sub($lockedUser->balance, $cost);
                $lockedUser->save();

                $order = Order::create([
                    'user_id' => $lockedUser->id,
                    'symbol' => $symbol,
                    'side' => $side,
                    'price' => $price,
                    'amount' => $amount,
                    'status' => Order::STATUS_OPEN,
                ]);
            } elseif ($side === Order::SIDE_SELL) {
                $asset = Asset::query()
                    ->where('user_id', $user->id)
                    ->where('symbol', $symbol)
                    ->lockForUpdate()
                    ->first();

                if (!$asset || Decimal::cmp($asset->amount, $amount) < 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient asset amount.',
                    ]);
                }

                $asset->amount = Decimal::sub($asset->amount, $amount);
                $asset->locked_amount = Decimal::add($asset->locked_amount, $amount);
                $asset->save();

                $order = Order::create([
                    'user_id' => $user->id,
                    'symbol' => $symbol,
                    'side' => $side,
                    'price' => $price,
                    'amount' => $amount,
                    'status' => Order::STATUS_OPEN,
                ]);
            } else {
                throw ValidationException::withMessages([
                    'side' => 'Invalid side.',
                ]);
            }

            MatchOrderJob::dispatch($order->id)->afterCommit();

            return $order;
        });
    }
}

