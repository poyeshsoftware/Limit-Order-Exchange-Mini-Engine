<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buy_order_id' => Order::factory()->state(['side' => Order::SIDE_BUY]),
            'sell_order_id' => Order::factory()->state(['side' => Order::SIDE_SELL]),
            'symbol' => fake()->randomElement(['BTC', 'ETH']),
            'price' => '100',
            'amount' => '1',
            'usd_volume' => '100',
            'fee_usd' => '1.50000000',
        ];
    }
}
