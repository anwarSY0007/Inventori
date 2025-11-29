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
| API Routes
|--------------------------------------------------------------------------
*/

// --- Categories ---
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{slug}', [CategoryController::class, 'show']);

    // Menggunakan POST untuk update (biasanya untuk handle file upload/multipart)
    // Jika frontend mengirim _method: PUT, ini tetap bisa ditangkap
    Route::post('/{slug}', [CategoryController::class, 'update']);
    Route::put('/{slug}', [CategoryController::class, 'update']); // Fallback standard REST

    Route::delete('/{slug}', [CategoryController::class, 'destroy']);
});

// --- Products ---
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{slug}', [ProductController::class, 'show']);

    Route::post('/{slug}', [ProductController::class, 'update']); // Handle multipart form data
    Route::put('/{slug}', [ProductController::class, 'update']);

    Route::delete('/{slug}', [ProductController::class, 'destroy']);
});

// --- Warehouses ---
Route::prefix('warehouses')->group(function () {
    // CRUD Warehouse
    Route::get('/', [WarehouseController::class, 'index']);
    Route::post('/', [WarehouseController::class, 'store']);
    Route::get('/{slug}', [WarehouseController::class, 'show']);
    Route::match(['put', 'patch', 'post'], '/{slug}', [WarehouseController::class, 'update']);
    Route::delete('/{slug}', [WarehouseController::class, 'destroy']);

    // Warehouse Products Management
    Route::prefix('{slug}/products')->group(function () {
        Route::get('/', [WarehouseProductController::class, 'getProducts']);
        Route::post('/', [WarehouseProductController::class, 'attachProduct']); // Initial stock / Restock
    });
});

// --- Merchants ---
Route::prefix('merchants')->group(function () {
    // CRUD Merchant
    Route::get('/', [MerchantController::class, 'index']);
    Route::post('/', [MerchantController::class, 'store']);
    Route::get('/{slug}', [MerchantController::class, 'show']);
    Route::match(['put', 'patch', 'post'], '/{slug}', [MerchantController::class, 'update']);
    Route::delete('/{slug}', [MerchantController::class, 'destroy']);

    // Nested Routes: Merchant Products
    Route::prefix('{merchantSlug}/products')->group(function () {
        Route::get('/', [MerchantProductController::class, 'getProducts']);
        Route::patch('/{productId}/stock', [MerchantProductController::class, 'updateStock']); // Manual Adjustment
        Route::delete('/{productId}', [MerchantProductController::class, 'detachProduct']);
    });

    // Nested Routes: Transaction Summary per Merchant
    Route::get('/{merchantId}/transactions/summary', [TransactionController::class, 'summary']);
});

// --- Merchant Product Operations (Global Actions) ---
Route::prefix('merchant-products')->group(function () {
    // Assign stock dari Warehouse ke Merchant (Mutation OUT Warehouse -> IN Merchant)
    Route::post('/assign', [MerchantProductController::class, 'assignProduct']);
});

// --- Transactions ---
Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index']); // Filterable list
    Route::post('/', [TransactionController::class, 'store']); // Checkout
    Route::get('/{id}', [TransactionController::class, 'show']);

    // Status Updates
    Route::patch('/{id}/status', [TransactionController::class, 'updateStatus']); // Mark as PAID/FAILED
    Route::post('/{id}/cancel', [TransactionController::class, 'cancel']); // Cancel & Restore Stock
});

// --- Stock Mutations (Reports & History) ---
Route::prefix('stock-mutations')->group(function () {
    Route::get('/', [StockMutationController::class, 'index']); // History Log

    // Reports
    Route::get('/reports/warehouse/{warehouseId}', [StockMutationController::class, 'warehouseReport']);
    Route::get('/reports/merchant/{merchantId}', [StockMutationController::class, 'merchantReport']);

    // Check Real-time Stock via Mutation Calculation
    Route::get('/stock/{productId}', [StockMutationController::class, 'currentStock']);
});
