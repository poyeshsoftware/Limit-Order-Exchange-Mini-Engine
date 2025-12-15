<?php

namespace App\Actions\Orders;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CancelOrderAction
{
    public function execute(User $user, Order $order): Order
    {
        return DB::transaction(function () use ($user, $order): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedOrder->user_id !== (int) $user->id) {
                throw new AccessDeniedHttpException();
            }

            if ($lockedOrder->status !== Order::STATUS_OPEN) {
                throw ValidationException::withMessages([
                    'status' => 'Only open orders can be cancelled.',
                ]);
            }

            if ($lockedOrder->side === Order::SIDE_BUY) {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $refund = Decimal::mul($lockedOrder->amount, $lockedOrder->price);
                $lockedUser->balance = Decimal::add($lockedUser->balance, $refund);
                $lockedUser->save();
            } elseif ($lockedOrder->side === Order::SIDE_SELL) {
                $asset = Asset::query()
                    ->where('user_id', $user->id)
                    ->where('symbol', $lockedOrder->symbol)
                    ->lockForUpdate()
                    ->first();

                if (!$asset) {
                    throw ValidationException::withMessages([
                        'symbol' => 'Asset row not found for this symbol.',
                    ]);
                }

                $asset->amount = Decimal::add($asset->amount, $lockedOrder->amount);
                $asset->locked_amount = Decimal::sub($asset->locked_amount, $lockedOrder->amount);
                $asset->save();
            }

            $lockedOrder->status = Order::STATUS_CANCELLED;
            $lockedOrder->save();

            return $lockedOrder;
        });
    }
}
