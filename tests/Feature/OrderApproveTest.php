<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApproveTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_approve_order_and_decrease_stock_and_balance()
    {
        $user = User::factory()->create(['balance' => 10000]);
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);
        $this->actingAs($user);
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'status' => Order::STATUS_PENDING,
        ]);
        $order->products()->attach($product->id, [
            'price' => $product->price,
            'stock_quantity' => 2,
            'customer_id' => $user->id,
        ]);
        $response = $this->postJson('/api/approve_order', [
            'order_id' => $order->id,
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success', 'order_id', 'charged'
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertEquals($order->id, $response->json('order_id'));
        $this->assertEquals(2000, $response->json('charged'));
    }

    public function test_approve_order_fails_if_not_enough_balance()
    {
        $user = User::factory()->create(['balance' => 100]);
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);
        $this->actingAs($user);
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'status' => Order::STATUS_PENDING,
        ]);
        $order->products()->attach($product->id, [
            'price' => $product->price,
            'stock_quantity' => 2,
            'customer_id' => $user->id,
        ]);
        $response = $this->postJson('/api/approve_order', [
            'order_id' => $order->id,
        ]);
        $response->assertStatus(400);
        $response->assertJsonStructure(['error', 'details' => ['message', 'code']]);
        $this->assertEquals(4003, $response->json('details.code'));
    }

    public function test_approve_order_fails_if_order_not_found()
    {
        $user = User::factory()->create(['balance' => 10000]);
        $this->actingAs($user);
        $response = $this->postJson('/api/approve_order', [
            'order_id' => 9999,
        ]);
        $response->assertStatus(404);
        $response->assertJsonStructure(['error', 'details' => ['message', 'code']]);
        $this->assertEquals(4041, $response->json('details.code'));
    }

    public function test_approve_order_fails_if_order_already_approved()
    {
        $user = User::factory()->create(['balance' => 10000]);
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);
        $this->actingAs($user);
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'status' => Order::STATUS_APPROVED,
        ]);
        $order->products()->attach($product->id, [
            'price' => $product->price,
            'stock_quantity' => 2,
            'customer_id' => $user->id,
        ]);
        $response = $this->postJson('/api/approve_order', [
            'order_id' => $order->id,
        ]);
        $response->assertStatus(400);
        $response->assertJsonStructure(['error', 'details' => ['message', 'code']]);
        $this->assertEquals(4002, $response->json('details.code'));
    }
}
