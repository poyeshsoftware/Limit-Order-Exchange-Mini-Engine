<?php

namespace Database\Seeders;

use App\Actions\Orders\CreateOrderAction;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::factory()->create([
            'name' => 'Demo Buyer',
            'email' => 'buyer@example.com',
            'balance' => '10000',
        ]);

        $seller = User::factory()->create([
            'name' => 'Demo Seller',
            'email' => 'seller@example.com',
            'balance' => '0',
        ]);

        Asset::query()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '2',
            'locked_amount' => '0',
        ]);

        Asset::query()->create([
            'user_id' => $seller->id,
            'symbol' => 'ETH',
            'amount' => '20',
            'locked_amount' => '0',
        ]);

        $makerBuy = User::factory()->create([
            'name' => 'Maker Buy',
            'email' => 'maker.buy@example.com',
            'balance' => '5000',
        ]);

        $makerSell = User::factory()->create([
            'name' => 'Maker Sell',
            'email' => 'maker.sell@example.com',
            'balance' => '0',
        ]);

        Asset::query()->create([
            'user_id' => $makerSell->id,
            'symbol' => 'BTC',
            'amount' => '1',
            'locked_amount' => '0',
        ]);

        Asset::query()->create([
            'user_id' => $makerSell->id,
            'symbol' => 'ETH',
            'amount' => '10',
            'locked_amount' => '0',
        ]);

        $createOrderAction = app(CreateOrderAction::class);

        $btcAmount = '0.15';
        foreach (['110', '115', '120', '125', '130'] as $price) {
            $createOrderAction->execute(
                user: $makerSell,
                symbol: 'BTC',
                side: 'sell',
                price: $price,
                amount: $btcAmount,
            );
        }

        foreach (['100', '95', '90', '85', '80'] as $price) {
            $createOrderAction->execute(
                user: $makerBuy,
                symbol: 'BTC',
                side: 'buy',
                price: $price,
                amount: $btcAmount,
            );
        }

        $ethAmount = '1.5';
        foreach (['55', '60', '65', '70', '75'] as $price) {
            $createOrderAction->execute(
                user: $makerSell,
                symbol: 'ETH',
                side: 'sell',
                price: $price,
                amount: $ethAmount,
            );
        }

        foreach (['50', '45', '40', '35', '30'] as $price) {
            $createOrderAction->execute(
                user: $makerBuy,
                symbol: 'ETH',
                side: 'buy',
                price: $price,
                amount: $ethAmount,
            );
        }

        // Default factory password is "password".
        $this->command?->info('Demo users seeded (password: "password"):');
        $this->command?->info('- buyer@example.com');
        $this->command?->info('- seller@example.com');
        $this->command?->info('- maker.buy@example.com');
        $this->command?->info('- maker.sell@example.com');
    }
}
