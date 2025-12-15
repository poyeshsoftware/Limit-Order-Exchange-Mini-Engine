<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExchangeEngineTest extends TestCase
{
    use DatabaseMigrations;

    public function test_buy_order_reserves_usd_balance(): void
    {
        $user = User::factory()->create([
            'balance' => '1000',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '100',
            'amount' => '2',
        ]);

        $response->assertStatus(201);

        $user->refresh();
        $this->assertSame('800.00000000', $user->balance);
    }

    public function test_sell_order_locks_asset_amount(): void
    {
        $user = User::factory()->create();

        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '2',
            'locked_amount' => '0',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '100',
            'amount' => '1',
        ]);

        $response->assertStatus(201);

        $asset = Asset::query()->where('user_id', $user->id)->where('symbol', 'BTC')->firstOrFail();
        $this->assertSame('1.00000000', $asset->amount);
        $this->assertSame('1.00000000', $asset->locked_amount);
    }

    public function test_cancel_buy_refunds_usd_balance(): void
    {
        $user = User::factory()->create([
            'balance' => '1000',
        ]);

        Sanctum::actingAs($user);

        $orderResponse = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '100',
            'amount' => '2',
        ]);

        $orderResponse->assertStatus(201);
        $orderId = (int) $orderResponse->json('id');

        $cancelResponse = $this->postJson("/api/orders/{$orderId}/cancel");
        $cancelResponse->assertOk();

        $user->refresh();
        $this->assertSame('1000.00000000', $user->balance);

        $order = Order::query()->findOrFail($orderId);
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_cancel_sell_releases_locked_asset_amount(): void
    {
        $user = User::factory()->create();

        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '2',
            'locked_amount' => '0',
        ]);

        Sanctum::actingAs($user);

        $orderResponse = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '100',
            'amount' => '1',
        ]);

        $orderResponse->assertStatus(201);
        $orderId = (int) $orderResponse->json('id');

        $cancelResponse = $this->postJson("/api/orders/{$orderId}/cancel");
        $cancelResponse->assertOk();

        $asset = Asset::query()->where('user_id', $user->id)->where('symbol', 'BTC')->firstOrFail();
        $this->assertSame('2.00000000', $asset->amount);
        $this->assertSame('0.00000000', $asset->locked_amount);

        $order = Order::query()->findOrFail($orderId);
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_matching_fills_orders_and_charges_fee(): void
    {
        $seller = User::factory()->create(['balance' => '0']);
        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1',
            'locked_amount' => '0',
        ]);

        $buyer = User::factory()->create(['balance' => '1000']);

        Sanctum::actingAs($seller);
        $sellResponse = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '90',
            'amount' => '1',
        ]);
        $sellResponse->assertStatus(201);
        $sellOrderId = (int) $sellResponse->json('id');

        Sanctum::actingAs($buyer);
        $buyResponse = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '100',
            'amount' => '1',
        ]);
        $buyResponse->assertStatus(201);
        $buyOrderId = (int) $buyResponse->json('id');

        $sellOrder = Order::query()->findOrFail($sellOrderId);
        $buyOrder = Order::query()->findOrFail($buyOrderId);
        $this->assertSame(Order::STATUS_FILLED, $sellOrder->status);
        $this->assertSame(Order::STATUS_FILLED, $buyOrder->status);

        $seller->refresh();
        $this->assertSame('90.00000000', $seller->balance);

        $buyer->refresh();
        $this->assertSame('908.65000000', $buyer->balance);

        $sellerAsset = Asset::query()->where('user_id', $seller->id)->where('symbol', 'BTC')->firstOrFail();
        $this->assertSame('0.00000000', $sellerAsset->amount);
        $this->assertSame('0.00000000', $sellerAsset->locked_amount);

        $buyerAsset = Asset::query()->where('user_id', $buyer->id)->where('symbol', 'BTC')->firstOrFail();
        $this->assertSame('1.00000000', $buyerAsset->amount);
        $this->assertSame('0.00000000', $buyerAsset->locked_amount);

        $trade = Trade::query()->firstOrFail();
        $this->assertSame($buyOrderId, $trade->buy_order_id);
        $this->assertSame($sellOrderId, $trade->sell_order_id);
        $this->assertSame('90.00000000', $trade->usd_volume);
        $this->assertSame('1.35000000', $trade->fee_usd);
    }

    public function test_user_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create(['balance' => '1000']);
        $attacker = User::factory()->create(['balance' => '1000']);

        Sanctum::actingAs($owner);
        $orderResponse = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '100',
            'amount' => '1',
        ]);
        $orderResponse->assertStatus(201);
        $orderId = (int) $orderResponse->json('id');

        Sanctum::actingAs($attacker);
        $this->postJson("/api/orders/{$orderId}/cancel")->assertForbidden();

        $order = Order::query()->findOrFail($orderId);
        $this->assertSame(Order::STATUS_OPEN, $order->status);
    }
}

