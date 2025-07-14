<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_order_creation_and_reservation()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($user);

        $response = $this->postJson('/api/create_order', [
            'products' => [
                ['id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', ['customer_id' => $user->id]);
        $this->assertDatabaseHas('product_reservations', [
            'product_id' => $product->id,
            'customer_id' => $user->id,
            'stock_quantity' => 5,
        ]);
    }

    public function test_order_creation_fails_if_not_enough_available_quantity()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 3]);

        // Создаём резервацию на всё количество
        $order = Order::factory()->create(['customer_id' => $user->id]);
        $product->reservations()->create([
            'price' => $product->price,
            'stock_quantity' => 3,
            'product_id' => $product->id,
            'customer_id' => $user->id,
            'order_id' => $order->id,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/create_order', [
            'products' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error', 'details' => ['message', 'code']]);
        $this->assertEquals(4001, $response->json('details.code'));
    }

    public function test_order_creation_fails_if_product_not_found()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/create_order', [
            'products' => [
                ['id' => 9999, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(404);
        $response->assertJsonStructure(['error', 'details' => ['message', 'code']]);
        $this->assertEquals(4041, $response->json('details.code'));
    }
}
