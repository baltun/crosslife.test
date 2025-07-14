<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/catalog', [\App\Http\Controllers\ProductsController::class, 'index']);
Route::post('/create_order', [\App\Http\Controllers\OrdersController::class, 'store']);
Route::post('/approve_order', [\App\Http\Controllers\OrdersController::class, 'approve']);
