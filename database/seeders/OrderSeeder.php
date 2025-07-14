<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users and products
        $users = User::all();
        $products = Product::all();

        // Create orders for each user
        foreach ($users as $user) {
            // Create 1-3 orders per user
            for ($i = 0; $i < rand(1, 3); $i++) {
                $order = Order::factory()->create([
                    'customer_id' => $user->id,
                    'status' => Order::STATUS_PENDING,
                ]);

                // Add 1-5 random products to each order
                $orderProducts = $products->random(rand(1, 5));
                foreach ($orderProducts as $product) {
                    $order->products()->attach($product->id, [
                        'quantity' => rand(1, 2),
                        'price' => $product->price,
                    ]);
                }
            }
        }
    }
}
