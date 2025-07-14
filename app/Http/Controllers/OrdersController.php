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
                'message' => $e->getMessage(),
                'code' => 5001
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

    /**
     * Подтвердить заказ: списать с баланса покупателя сумму и сменить статус заказа
     */
    public function approve(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'error' => 'Ошибка подтверждения заказа',
                'details' => [
                    'message' => 'Пользователь не авторизован',
                    'code' => 4011
                ]
            ], 401);
        }
        try {
            $order = Order::with('products')->where('id', $request->order_id)->where('customer_id', $user->id)->first();
            if (!$order) {
                throw new \RuntimeException(json_encode([
                    'message' => 'Заказ не найден',
                    'code' => 4041
                ]));
            }
            if ($order->status !== Order::STATUS_PENDING) {
                throw new \RuntimeException(json_encode([
                    'message' => 'Заказ уже подтверждён или не ожидает подтверждения',
                    'code' => 4002
                ]));
            }
            $total = 0;
            foreach ($order->products as $product) {
                $total += $product->pivot->price * $product->pivot->stock_quantity;
            }
            if ($user->balance < $total) {
                throw new \RuntimeException(json_encode([
                    'message' => 'Недостаточно средств на балансе',
                    'code' => 4003
                ]));
            }
            DB::beginTransaction();
            $user->balance -= $total;
            $user->save();
            foreach ($order->products as $product) {
                $product->stock_quantity -= $product->pivot->stock_quantity;
                $product->save();
            }
            $order->status = Order::STATUS_APPROVED;
            $order->save();
            DB::commit();
            return response()->json(['success' => true, 'order_id' => $order->id, 'charged' => $total]);
        } catch (\Exception $e) {
            DB::rollBack();
            $details = [
                'message' => $e->getMessage(),
                'code' => 5001
            ];
            $status = 500;
            if ($e instanceof \RuntimeException) {
                $json = json_decode($e->getMessage(), true);
                if (is_array($json) && isset($json['message'], $json['code'])) {
                    $details = $json;
                    $status = (int)substr((string)$json['code'], 0, 3);
                }
            }
            return response()->json(['error' => 'Ошибка подтверждения заказа', 'details' => $details], $status);
        }
    }

    private function getAvailableProductQuantity(Product $product): int
    {
        $reservedQty = $product->reservations()
            ->whereHas('order', function($q) {
                $q->where('status', Order::STATUS_PENDING);
            })
            ->sum('stock_quantity');
        return $product->stock_quantity - $reservedQty;
    }
}
