<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\MerchantProductController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockMutationController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\WarehouseProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Stock Management System
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // ========================================
    // CATEGORIES
    // ========================================
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/{category:slug}', [CategoryController::class, 'show'])->name('show');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{category:slug}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category:slug}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // ========================================
    // PRODUCTS
    // ========================================
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{product:slug}', [ProductController::class, 'show'])->name('show');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::put('/{product:slug}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product:slug}', [ProductController::class, 'destroy'])->name('destroy');
    });

    // ========================================
    // WAREHOUSES
    // ========================================
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/{warehouse:slug}', [WarehouseController::class, 'show'])->name('show');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');
        Route::put('/{warehouse:slug}', [WarehouseController::class, 'update'])->name('update');
        Route::delete('/{warehouse:slug}', [WarehouseController::class, 'destroy'])->name('destroy');

        // Warehouse Products Management
        Route::prefix('{warehouse:slug}/products')->name('products.')->group(function () {
            Route::get('/', [WarehouseProductController::class, 'getProducts'])->name('index');
            Route::post('/', [WarehouseProductController::class, 'attachProduct'])->name('attach');
            Route::put('/{product}', [WarehouseProductController::class, 'updateStock'])->name('update-stock');
            Route::delete('/{product}', [WarehouseProductController::class, 'detachProduct'])->name('detach');

            // Stock Transfer Endpoints
            Route::post('/{product}/transfer', [WarehouseProductController::class, 'transferToMerchant'])
                ->name('transfer');
            Route::get('/{product}/transfers', [WarehouseProductController::class, 'getTransferHistory'])
                ->name('transfer-history');
            Route::post('/batch-transfer', [WarehouseProductController::class, 'batchTransferToMerchant'])
                ->name('batch-transfer');
        });
    });

    // ========================================
    // MERCHANTS
    // ========================================
    Route::prefix('merchants')->name('merchants.')->group(function () {
        Route::get('/', [MerchantController::class, 'index'])->name('index');
        Route::get('/my-merchant', [MerchantController::class, 'showByKeeper'])->name('my-merchant');
        Route::get('/{merchant:slug}', [MerchantController::class, 'show'])->name('show');
        Route::post('/', [MerchantController::class, 'store'])->name('store');
        Route::put('/{merchant:slug}', [MerchantController::class, 'update'])->name('update');
        Route::delete('/{merchant:slug}', [MerchantController::class, 'destroy'])->name('destroy');

        // Merchant Products Management
        Route::prefix('{merchant:slug}/products')->name('products.')->group(function () {
            Route::get('/', [MerchantProductController::class, 'getProducts'])->name('index');
            Route::post('/', [MerchantProductController::class, 'attachProduct'])->name('attach');
            Route::put('/{product}', [MerchantProductController::class, 'updateStock'])->name('update-stock');
            Route::delete('/{product}', [MerchantProductController::class, 'detachProduct'])->name('detach');

            // Stock Movement Endpoints
            Route::post('/{product}/return', [MerchantProductController::class, 'returnToWarehouse'])
                ->name('return');
            Route::post('/{product}/transfer', [MerchantProductController::class, 'transferToMerchant'])
                ->name('transfer');
            Route::get('/{product}/movements', [MerchantProductController::class, 'getMovementHistory'])
                ->name('movements');
        });
    });

    // ========================================
    // TRANSACTIONS
    // ========================================
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::get('/invoice/{invoiceCode}', [TransactionController::class, 'showByInvoice'])->name('show-by-invoice');
        Route::post('/', [TransactionController::class, 'store'])->name('store');

        // Transaction Status Management
        Route::put('/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('update-status');
        Route::post('/{transaction}/pay', [TransactionController::class, 'markAsPaid'])->name('mark-paid');
        Route::post('/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('cancel');

        // Transaction Reports
        Route::get('/merchants/{merchant}/summary', [TransactionController::class, 'summary'])->name('summary');
    });

    // ========================================
    // STOCK MUTATIONS
    // ========================================
    Route::prefix('stock-mutations')->name('stock-mutations.')->group(function () {
        Route::get('/', [StockMutationController::class, 'index'])->name('index');
        Route::get('/{mutation}', [StockMutationController::class, 'show'])->name('show');
        Route::post('/', [StockMutationController::class, 'store'])->name('store');

        // Mutation Reports
        Route::get('/products/{product}', [StockMutationController::class, 'byProduct'])->name('by-product');
        Route::get('/reference', [StockMutationController::class, 'byReference'])->name('by-reference');
        Route::get('/products/{product}/history', [StockMutationController::class, 'productHistory'])->name('product-history');
        Route::get('/products/{product}/current-stock', [StockMutationController::class, 'currentStock'])->name('current-stock');

        // Location-based Reports
        Route::get('/warehouses/{warehouse}/report', [StockMutationController::class, 'warehouseReport'])->name('warehouse-report');
        Route::get('/merchants/{merchant}/report', [StockMutationController::class, 'merchantReport'])->name('merchant-report');
    });
});

// ========================================
// PUBLIC ROUTES (Guest Access)
// ========================================
Route::prefix('public')->name('public.')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
});
