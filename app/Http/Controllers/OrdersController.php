<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|integer',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_number' => uniqid('order_'),
                'status' => Order::STATUS_PENDING,
                'date' => now(),
                'customer_id' => $user->id,
            ]);

            foreach ($data['products'] as $item) {
                $product = Product::lockForUpdate()->find($item['id']);
                if (!$product) {
                    throw new \RuntimeException(json_encode([
                        'message' => 'Товар не найден: ' . $item['id'],
                        'code' => 4041
                    ]));
                }
                $availableQty = $this->getAvailableProductQuantity($product);
                if ($availableQty < $item['quantity']) {
                    throw new \RuntimeException(json_encode([
                        'message' => 'Недостаточно товара: ' . $product->name . '. Доступно: ' . $availableQty,
                        'code' => 4001
                    ]));
                }
                ProductReservation::create([
                    'price' => $product->price,
                    'stock_quantity' => $item['quantity'],
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'customer_id' => $user->id,
                ]);
            }

            DB::commit();
            return response()->json(['order_id' => $order->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            $details = [
                'message' => 'Ошибка создания заказа',
                'code' => 5000
            ];
            $status = 500;
            if ($e instanceof \RuntimeException) {
                $json = json_decode($e->getMessage(), true);
                if (is_array($json) && isset($json['message'], $json['code'])) {
                    $details = $json;
                    $status = (int)substr((string)$json['code'], 0, 3);
                }
            }
            return response()->json(['error' => 'Ошибка создания заказа', 'details' => $details], $status);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function getAvailableProductQuantity(Product $product): int
    {
        $reservedQty = $product->reservations()->sum('stock_quantity');
        return $product->stock_quantity - $reservedQty;
    }
}
