
<?php

use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\MerchantProductController;
use App\Http\Controllers\api\WarehouseController;
use App\Http\Controllers\api\WarehouseProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->group(function () {

    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);

    Route::get('/{slug}', [CategoryController::class, 'show']);
    Route::post('/{slug}', [CategoryController::class, 'update']);
    Route::delete('/{slug}', [CategoryController::class, 'destroy']);
});
Route::prefix('warehouses')->group(function () {
    // Standard CRUD
    Route::get('/', [WarehouseController::class, 'index']);
    Route::get('/{slug}', [WarehouseController::class, 'show']);
    Route::post('/', [WarehouseController::class, 'store']);
    Route::put('/{slug}', [WarehouseController::class, 'update']);
    Route::patch('/{slug}', [WarehouseController::class, 'update']);
    Route::delete('/{slug}', [WarehouseController::class, 'destroy']);

    // Product Management Routes (Menggunakan WarehouseProductController)
    Route::prefix('{slug}/products')->group(function () {
        Route::get('/', [WarehouseProductController::class, 'getProducts']);
        Route::post('/', [WarehouseProductController::class, 'attachProduct']);
        Route::put('/{productId}/stock', [WarehouseProductController::class, 'updateStock']);
        Route::patch('/{productId}/stock', [WarehouseProductController::class, 'updateStock']);
        Route::delete('/{productId}', [WarehouseProductController::class, 'detachProduct']);
    });
});
// Merchant CRUD Routes
Route::prefix('merchants')->group(function () {
    // Standard CRUD
    Route::get('/', [MerchantController::class, 'index']);
    Route::get('/{slug}', [MerchantController::class, 'show']);
    Route::post('/', [MerchantController::class, 'store']);
    Route::put('/{slug}', [MerchantController::class, 'update']);
    Route::patch('/{slug}', [MerchantController::class, 'update']);
    Route::delete('/{slug}', [MerchantController::class, 'destroy']);
    Route::get('/keeper/{keeperId}', [MerchantController::class, 'showByKeeper']);
    Route::prefix('{slug}/products')->group(function () {
        Route::get('/', [MerchantProductController::class, 'getProducts']);
        Route::post('/', [MerchantProductController::class, 'attachProduct']);
        Route::put('/{productId}/stock', [MerchantProductController::class, 'updateStock']);
        Route::patch('/{productId}/stock', [MerchantProductController::class, 'updateStock']);
        Route::delete('/{productId}', [MerchantProductController::class, 'detachProduct']);
    });
});
Route::prefix('merchant-products')->group(function () {
    Route::post('/assign-from-warehouse', [MerchantProductController::class, 'assignFromWarehouse']);
    Route::post('/return-to-warehouse', [MerchantProductController::class, 'returnToWarehouse']);
    Route::post('/transfer', [MerchantProductController::class, 'transferBetweenMerchants']);
    Route::get('/{merchantId}/products/{productId}/movements', [MerchantProductController::class, 'getStockMovement']);
});
