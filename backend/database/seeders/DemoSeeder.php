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

        $createOrderAction->execute(
            user: $makerSell,
            symbol: 'BTC',
            side: 'sell',
            price: '110',
            amount: '0.15',
        );

        $createOrderAction->execute(
            user: $makerBuy,
            symbol: 'BTC',
            side: 'buy',
            price: '90',
            amount: '0.10',
        );

        $createOrderAction->execute(
            user: $makerSell,
            symbol: 'ETH',
            side: 'sell',
            price: '55',
            amount: '1.5',
        );

        $createOrderAction->execute(
            user: $makerBuy,
            symbol: 'ETH',
            side: 'buy',
            price: '35',
            amount: '2',
        );

        // Default factory password is "password".
        $this->command?->info('Demo users seeded (password: "password"):');
        $this->command?->info('- buyer@example.com');
        $this->command?->info('- seller@example.com');
        $this->command?->info('- maker.buy@example.com');
        $this->command?->info('- maker.sell@example.com');
    }
}

