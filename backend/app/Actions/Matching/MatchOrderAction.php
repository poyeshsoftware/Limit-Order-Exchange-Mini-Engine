<?php

namespace App\Actions\Matching;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class MatchOrderAction
{
    /**
     * @return array{symbol:string,buyer_id:int,seller_id:int,buy_order_id:int,sell_order_id:int,trade_id:int}|null
     */
    public function execute(int $incomingOrderId): ?array
    {
        return DB::transaction(function () use ($incomingOrderId): ?array {
            $incoming = Order::query()
                ->whereKey($incomingOrderId)
                ->lockForUpdate()
                ->first();

            if (!$incoming || $incoming->status !== Order::STATUS_OPEN) {
                return null;
            }

            $counter = $this->findCounterOrder($incoming);

            if (!$counter) {
                return null;
            }

            if (Decimal::cmp($counter->amount, $incoming->amount) !== 0) {
                return null;
            }

            $buyOrder = $incoming->side === Order::SIDE_BUY ? $incoming : $counter;
            $sellOrder = $incoming->side === Order::SIDE_SELL ? $incoming : $counter;

            $tradePrice = $counter->price;
            $amount = $incoming->amount;
            $usdVolume = Decimal::mul($amount, $tradePrice);
            $feeUsd = Decimal::mul($usdVolume, Decimal::FEE_RATE);

            $buyer = User::query()->whereKey($buyOrder->user_id)->lockForUpdate()->firstOrFail();
            $seller = User::query()->whereKey($sellOrder->user_id)->lockForUpdate()->firstOrFail();

            $sellerAsset = Asset::query()
                ->where('user_id', $seller->id)
                ->where('symbol', $incoming->symbol)
                ->lockForUpdate()
                ->firstOrFail();

            $reservedCost = Decimal::mul($amount, $buyOrder->price);
            $refund = Decimal::sub($reservedCost, $usdVolume);

            $buyerBalanceAfterRefund = Decimal::add($buyer->balance, $refund);
            if (Decimal::cmp($buyerBalanceAfterRefund, $feeUsd) < 0) {
                return null;
            }

            if (Decimal::cmp($sellerAsset->locked_amount, $amount) < 0) {
                return null;
            }

            $buyerAsset = $this->lockOrCreateAssetRow($buyer->id, $incoming->symbol);

            $buyerAsset->amount = Decimal::add($buyerAsset->amount, $amount);
            $buyerAsset->save();

            $sellerAsset->locked_amount = Decimal::sub($sellerAsset->locked_amount, $amount);
            $sellerAsset->save();

            $buyer->balance = Decimal::sub($buyerBalanceAfterRefund, $feeUsd);
            $buyer->save();

            $seller->balance = Decimal::add($seller->balance, $usdVolume);
            $seller->save();

            $incoming->status = Order::STATUS_FILLED;
            $incoming->save();

            $counter->status = Order::STATUS_FILLED;
            $counter->save();

            $trade = Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'symbol' => $incoming->symbol,
                'price' => $tradePrice,
                'amount' => $amount,
                'usd_volume' => $usdVolume,
                'fee_usd' => $feeUsd,
            ]);

            return [
                'symbol' => $incoming->symbol,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'trade_id' => $trade->id,
            ];
        });
    }

    private function findCounterOrder(Order $incoming): ?Order
    {
        $query = Order::query()
            ->where('symbol', $incoming->symbol)
            ->where('status', Order::STATUS_OPEN);

        if ($incoming->side === Order::SIDE_BUY) {
            $query
                ->where('side', Order::SIDE_SELL)
                ->where('price', '<=', $incoming->price)
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc');
        } else {
            $query
                ->where('side', Order::SIDE_BUY)
                ->where('price', '>=', $incoming->price)
                ->orderBy('price', 'desc')
                ->orderBy('created_at', 'asc');
        }

        return $query->lockForUpdate()->first();
    }

    private function lockOrCreateAssetRow(int $userId, string $symbol): Asset
    {
        $asset = Asset::query()
            ->where('user_id', $userId)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();

        if ($asset) {
            return $asset;
        }

        try {
            Asset::create([
                'user_id' => $userId,
                'symbol' => $symbol,
                'amount' => '0',
                'locked_amount' => '0',
            ]);
        } catch (QueryException) {
            // Another transaction created the row.
        }

        return Asset::query()
            ->where('user_id', $userId)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
