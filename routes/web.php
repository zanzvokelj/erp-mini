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
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\ReorderController;
use App\Http\Controllers\OrderItem;
use App\Http\Controllers\TransferController;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'allowed.admin'])->group(function () {
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


    Route::get('/orders/export', [OrderController::class, 'export'])
        ->name('orders.export');

    Route::get('/orders/{order}/activity', [OrderController::class, 'activity'])
        ->name('orders.activity');

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


    Route::post('/purchase-orders/{po}/items',
        [PurchaseOrderController::class,'addItem']
    )->name('purchase-orders.items.add');

    Route::post('/purchase-orders/{po}/order',
        [PurchaseOrderController::class,'order']
    )->name('purchase-orders.order');

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

    Route::post('/purchase-orders/{po}/receive', [PurchaseOrderController::class,'receive'])
        ->middleware('auth')
        ->name('purchase-orders.receive');

    Route::post('/purchase-orders/{po}/payments', [PurchaseOrderController::class,'recordPayment'])
        ->middleware('auth')
        ->name('purchase-orders.payments.store');

    Route::get('/purchase-orders', [PurchaseOrderController::class,'index'])
        ->middleware('auth')
        ->name('purchase-orders.index');

    Route::get('/purchase-orders/create', [PurchaseOrderController::class,'create'])
        ->middleware('auth')
        ->name('purchase-orders.create');

    Route::post('/purchase-orders', [PurchaseOrderController::class,'store'])
        ->middleware('auth')
        ->name('purchase-orders.store');

    Route::get('/purchase-orders/{po}', [PurchaseOrderController::class,'show'])
        ->middleware('auth')
        ->name('purchase-orders.show');

    Route::post('/orders/{order}/return', [OrderController::class, 'returnOrder'])
        ->name('orders.return');

    Route::get('/reorder-suggestions', [ReorderController::class,'index'])
        ->name('reorder.index');

    Route::post('/reorder/create-po', [ReorderController::class,'createPO'])
        ->name('reorder.createPO');

    Route::get('/stock-movements', [StockMovementController::class, 'index'])
        ->middleware('auth')
        ->name('stock-movements.index');

    Route::post('/stock-adjust', [StockController::class, 'adjust']);

    Route::get('/dashboard', [DashboardController::class, 'dashboard'])
        ->middleware(['auth','verified'])
        ->name('dashboard');

    Route::get('/customers/{customer}', [CustomerController::class,'show'])
        ->name('customers.show');

    Route::get('/dashboard-data', [DashboardController::class, 'index'])
        ->middleware('auth');

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });


    Route::middleware(['auth', 'role:warehouse'])->group(function () {
        Route::post('/stock-adjust', [StockController::class, 'adjust']);
    });

    Route::get('/invoices', function () {
        return view('invoices.index');
    })->middleware('auth')->name('invoices.index');

    Route::get('/invoices/create', function () {
        return view('invoices.create');
    })->middleware('auth')->name('invoices.create');

    Route::get('/invoices/{invoice}', function ($invoice) {
        return view('invoices.show', ['invoiceId' => $invoice]);
    })->middleware('auth')->name('invoices.show');

    Route::get('/finance', function () {
        return view('finance.index');
    })->name('finance.index');

    Route::get('/finance/journal-entries', [JournalEntryController::class, 'index'])
        ->name('finance.journal-entries.index');

    Route::get('/transfers', [TransferController::class, 'index'])
        ->name('transfers.index');

    Route::get('/transfers/create', [TransferController::class, 'create'])
        ->name('transfers.create');

    Route::post('/transfers', [TransferController::class, 'store'])
        ->name('transfers.store');
});

Route::get('/api/docs', function () {
    return view('swagger');
});


require __DIR__.'/auth.php';
