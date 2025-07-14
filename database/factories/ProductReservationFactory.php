<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\ProductReservation;

class ProductReservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductReservation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'price' => fake()->numberBetween(100, 10000),
            'stock_quantity' => fake()->numberBetween(1, 5),
            'product_id' => Product::factory(),
            'customer_id' => User::factory(),
            'order_id' => Order::factory(),
        ];
    }
}
