<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the index endpoint returns a list of products
     *
     */
    public function test_index_returns_list_of_products()
    {
        // Create some test products
        Product::factory()->count(3)->create();

        // Make the API request
        $response = $this->getJson('/api/catalog');

        dd($response->json());
        // Assert the response
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

        // Verify the number of products returned
        $this->assertCount(3, $response->json('data'));
    }
}
