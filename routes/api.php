<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\OrderApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\SupplierApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;

Route::prefix('v1')->group(function () {

    Route::get('/products', [ProductApiController::class, 'index']);
    Route::get('/products/{product}', [ProductApiController::class, 'show']);
    Route::get('/products/{product}/stock-history', [ProductApiController::class, 'stockHistory']);

    Route::get('/orders', [OrderApiController::class, 'index']);
    Route::get('/orders/{order}', [OrderApiController::class, 'show']);
    Route::post('/orders', [OrderApiController::class, 'store']);
    Route::post('/orders/{order}/items', [OrderApiController::class, 'addItem']);
    Route::patch('/orders/items/{item}', [OrderApiController::class, 'updateItem']);
    Route::delete('/orders/items/{item}', [OrderApiController::class, 'removeItem']);
    Route::post('/orders/{order}/confirm', [OrderApiController::class, 'confirm']);
    Route::post('/orders/{order}/ship', [OrderApiController::class, 'ship']);
    Route::post('/orders/{order}/complete', [OrderApiController::class, 'complete']);
    Route::post('/orders/{order}/cancel', [OrderApiController::class, 'cancel']);

    Route::get('/customers', [CustomerApiController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerApiController::class, 'show']);

    Route::get('/suppliers', [SupplierApiController::class, 'index']);
    Route::get('/suppliers/{supplier}', [SupplierApiController::class, 'show']);

    Route::get('/inventory', [InventoryApiController::class, 'index']);
    Route::post('/inventory/adjust', [InventoryApiController::class, 'adjust']);
    Route::get('/stock-movements', [InventoryApiController::class, 'movements']);
});
