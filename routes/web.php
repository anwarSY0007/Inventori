<?php


use App\Http\Controllers\Web\MerchantController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\StockMutationController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Guest Routes - Registration
Route::middleware('guest')->group(function () {
    Route::get('/register/merchant', [UserController::class, 'createMerchant'])
        ->name('register.merchant');
    Route::post('/register/merchant', [UserController::class, 'storeMerchant']);
});

// Authenticated & Verified Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserController::class, 'profile'])->name('show');
        Route::get('/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/', [UserController::class, 'update'])->name('update');
    });

    // Team Management
    Route::post('/team/switch', [UserController::class, 'switchTeam'])
        ->name('team.switch');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/{id}', [UserController::class, 'show'])->name('show');
            Route::post('/{id}/assign-role', [UserController::class, 'assignRole'])->name('assign-role');
        });

        // Products
        Route::resource('products', ProductController::class);

        // Merchants
        Route::resource('merchants', MerchantController::class);
        Route::get('merchants/{merchant:slug}/products', [MerchantController::class, 'products'])
            ->name('merchants.products');

        // Warehouses
        Route::resource('warehouses', WarehouseController::class);
        Route::get('warehouses/{warehouse:slug}/products', [WarehouseController::class, 'products'])
            ->name('warehouses.products');
        Route::get('warehouses/{warehouse:slug}/transfer', [WarehouseController::class, 'transfer'])
            ->name('warehouses.transfer');

        // Transactions
        Route::resource('transactions', TransactionController::class)->only(['index', 'show']);
        Route::get('transactions/reports', [TransactionController::class, 'reports'])
            ->name('transactions.reports');

        // Stock Mutations
        Route::get('stock-mutations', [StockMutationController::class, 'index'])
            ->name('stock-mutations.index');
        Route::get('stock-mutations/product/{product}', [StockMutationController::class, 'productHistory'])
            ->name('stock-mutations.product-history');
        Route::get('stock-mutations/warehouse/{warehouse}', [StockMutationController::class, 'warehouseReport'])
            ->name('stock-mutations.warehouse-report');
        Route::get('stock-mutations/merchant/{merchant}', [StockMutationController::class, 'merchantReport'])
            ->name('stock-mutations.merchant-report');
        Route::get('stock-levels', [StockMutationController::class, 'stockLevels'])
            ->name('stock-levels');
    });

    // Cashier Routes
    Route::prefix('cashier')->name('cashier.')->middleware('role:cashier|merchant_owner')->group(function () {
        Route::get('pos', [TransactionController::class, 'create'])
            ->name('transactions.create');
        Route::post('pos', [TransactionController::class, 'store'])
            ->name('transactions.store');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])
            ->name('transactions.show');
        Route::get('transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])
            ->name('transactions.receipt');
    });
});

// require __DIR__ . '/settings.php';
