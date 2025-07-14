<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Customer;
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
            'customer_id' => Customer::factory(),
        ];
    }
}
