<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_list_of_products()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/catalog');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'stock_quantity',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true
            ]);

        $this->assertCount(3, $response->json('data'));
    }
}
