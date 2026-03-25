<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\OrderApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\SupplierApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;
use App\Http\Controllers\Api\V1\InvoiceApiController;
use App\Http\Controllers\Api\V1\PaymentApiController;
use App\Http\Controllers\Api\V1\FinanceApiController;


Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'allowed.admin'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // PRODUCTS
        Route::controller(ProductApiController::class)->group(function () {
            Route::get('/products', 'index');
            Route::get('/products/{product}', 'show');
            Route::get('/products/{product}/stock-history', 'stockHistory');
        });

        // ORDERS
        Route::controller(OrderApiController::class)->group(function () {
            Route::get('/orders/invoicable', 'invoicable');
            Route::get('/orders', 'index');
            Route::get('/orders/{order}', 'show');

            Route::post('/orders', 'store');
            Route::post('/orders/{order}/items', 'addItem');
            Route::patch('/orders/items/{item}', 'updateItem');
            Route::delete('/orders/items/{item}', 'removeItem');
            Route::post('/orders/{order}/confirm', 'confirm');
            Route::post('/orders/{order}/ship', 'ship');
            Route::post('/orders/{order}/complete', 'complete');
            Route::post('/orders/{order}/cancel', 'cancel');
            Route::post('/orders/{order}/invoice', 'createInvoice');
        });

        // CUSTOMERS
        Route::controller(CustomerApiController::class)->group(function () {
            Route::get('/customers', 'index');
            Route::get('/customers/{customer}', 'show');
        });

        // SUPPLIERS
        Route::controller(SupplierApiController::class)->group(function () {
            Route::get('/suppliers', 'index');
            Route::get('/suppliers/{supplier}', 'show');
        });

        // INVENTORY
        Route::controller(InventoryApiController::class)->group(function () {
            Route::get('/inventory', 'index');
            Route::get('/stock-movements', 'movements');

            Route::post('/inventory/adjust', 'adjust');
        });

        // INVOICES
        Route::controller(InvoiceApiController::class)->group(function () {
            Route::get('/invoices', 'index');
            Route::get('/invoices/{invoice}', 'show');
            Route::get('/invoices/{invoice}/pdf', 'pdf');
            Route::get('/invoices/overdue', 'overdue');
        });


        Route::controller(PaymentApiController::class)->group(function () {
            Route::post('/invoices/{invoice}/payments', 'store');
        });


        Route::get('/finance/overview', [FinanceApiController::class, 'overview']);
    });
});
