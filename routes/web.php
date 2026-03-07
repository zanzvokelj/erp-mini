<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockMovementController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/products', [ProductController::class, 'index'])
    ->middleware('auth')
    ->name('products.index');
Route::post('/products', [ProductController::class, 'store']);


Route::get('/orders', [OrderController::class, 'index'])
    ->middleware('auth')
    ->name('orders.index');

Route::get('/orders/create', [OrderController::class, 'create'])
    ->middleware('auth')
    ->name('orders.create');

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['auth','role:sales'])
    ->name('orders.store');

Route::get('/orders/{order}', [OrderController::class, 'show'])
    ->middleware('auth')
    ->name('orders.show');

Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])
    ->middleware('auth')
    ->name('orders.confirm');

Route::post('/orders/{order}/items', [OrderController::class, 'addItem'])
    ->middleware('auth')
    ->name('orders.items.add');

Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])
    ->middleware('auth')
    ->name('orders.ship');

Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])
    ->middleware('auth')
    ->name('orders.complete');

Route::patch('/orders/items/{item}', [OrderController::class, 'updateItem'])
    ->name('orders.items.update');

Route::delete('/orders/items/{item}', [OrderController::class, 'removeItem'])
    ->name('orders.items.remove');

Route::post('/orders/{order}/cancel',[OrderController::class,'cancel'])
    ->name('orders.cancel');


Route::resource('products', ProductController::class);

Route::get('/customers', [CustomerController::class, 'index'])
    ->middleware('auth')
    ->name('customers.index');


Route::get('/suppliers', [SupplierController::class, 'index'])
    ->middleware('auth')
    ->name('suppliers.index');

Route::get('/inventory', [InventoryController::class, 'index'])
    ->middleware('auth')
    ->name('inventory.index');

Route::get('/api/products/search', [ProductController::class, 'search'])
    ->middleware('auth')
    ->name('products.search');


Route::get('/stock-movements', [StockMovementController::class, 'index'])
    ->middleware('auth')
    ->name('stock-movements.index');

Route::post('/stock-adjust', [StockController::class, 'adjust']);

Route::get('/dashboard', [DashboardController::class, 'dashboard'])
    ->middleware(['auth','verified'])
    ->name('dashboard');

Route::get('/dashboard-data', [DashboardController::class, 'index'])
    ->middleware('auth');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
});


Route::middleware(['auth', 'role:warehouse'])->group(function () {
    Route::post('/stock-adjust', [StockController::class, 'adjust']);
});

require __DIR__.'/auth.php';
