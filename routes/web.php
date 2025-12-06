<?php

use App\Enum\PermissionEnum;
use App\Enum\RolesEnum;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MerchantController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\StockMutationController;
use App\Http\Controllers\Web\TeamMemberController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication / Registration Routes (Guest)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/register/merchant', [UserController::class, 'createMerchant'])
        ->name('register.merchant');
    Route::post('/register/merchant', [UserController::class, 'storeMerchant'])
        ->name('register.merchant.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // --- Common Routes (Accessible by all auth users) ---
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // User Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserController::class, 'profile'])->name('show');
        Route::get('/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/', [UserController::class, 'update'])->name('update');
    });

    // Team Switching (Users with multiple teams)
    Route::post('/switch-team', [UserController::class, 'switchTeam'])
        ->name('switch-team');

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN MODULE
    |--------------------------------------------------------------------------
    | Full system access - manage all users, merchants, and customers
    */
    Route::middleware(['role:' . RolesEnum::SUPER_ADMIN->value])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // All Users Management
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('/{id}', [UserController::class, 'show'])->name('show');
                Route::post('/{id}/assign-role', [UserController::class, 'assignRole'])->name('assign-role');
            });

            // All Customers Across All Teams
            Route::get('/customers/all', [UserController::class, 'allCustomers'])
                ->name('customers.all');

            // Manage Merchants
            Route::middleware(['can:' . PermissionEnum::MANAGE_MERCHANTS->value])->group(function () {
                Route::get('/merchants/{slug}/products', [MerchantController::class, 'products'])
                    ->name('merchants.products');
                Route::resource('merchants', MerchantController::class)
                    ->parameters(['merchants' => 'slug']);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | TEAM MANAGEMENT MODULE
    |--------------------------------------------------------------------------
    | Merchant Owner & Admin manage their team members and customers
    */
    Route::middleware(['role:' . RolesEnum::MERCHANT_OWNER->value . '|' . RolesEnum::ADMIN->value])
        ->prefix('team')
        ->name('team.')
        ->group(function () {

            // Team Members Management (Staff only - not customers)
            Route::prefix('members')->name('members.')->group(function () {
                Route::get('/', [TeamMemberController::class, 'index'])->name('index');
                Route::post('/', [TeamMemberController::class, 'store'])->name('store');
                Route::put('/{userId}', [TeamMemberController::class, 'update'])->name('update');
                Route::delete('/{userId}', [TeamMemberController::class, 'destroy'])->name('destroy');
            });

            // Team Customers Management
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', [CustomerController::class, 'Index'])->name('index');
                Route::get('/{customerId}', [CustomerController::class, 'show'])->name('show');
            });
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF ACCESS - View Customers Only
    |--------------------------------------------------------------------------
    | Cashier & Warehouse Staff can view customers (read-only)
    */
    Route::middleware(['role:' . RolesEnum::CASHIER->value . '|' . RolesEnum::WAREHOUSE_STAFF->value])
        ->prefix('customers')
        ->name('customers.')
        ->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/{customerId}', [CustomerController::class, 'show'])->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | ADMIN MODULE - General Management
    |--------------------------------------------------------------------------
    | Products, Categories, Warehouses, Stock, Reports
    */
    Route::prefix('admin')->name('admin.')->group(function () {

        // Manage Categories
        Route::middleware(['can:' . PermissionEnum::MANAGE_CATEGORIES->value])->group(function () {
            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        });

        // Manage Warehouses
        Route::middleware(['can:' . PermissionEnum::MANAGE_WAREHOUSES->value])->group(function () {
            Route::get('/warehouses/{slug}/products', [WarehouseController::class, 'products'])
                ->name('warehouses.products');
            Route::get('/warehouses/{slug}/transfer', [WarehouseController::class, 'transfer'])
                ->name('warehouses.transfer');
            Route::resource('warehouses', WarehouseController::class)
                ->parameters(['warehouses' => 'slug']);
        });

        // Manage Global Products
        Route::middleware(['can:' . PermissionEnum::MANAGE_GLOBAL_PRODUCTS->value])->group(function () {
            Route::resource('products', ProductController::class)
                ->parameters(['products' => 'slug']);
        });

        // Stock Management & Reports
        Route::prefix('stock')->name('stock.')->group(function () {
            Route::middleware(['can:' . PermissionEnum::MANAGE_WAREHOUSE_STOCK->value])->group(function () {
                Route::get('/', [StockMutationController::class, 'index'])->name('index');
                Route::get('/levels', [StockMutationController::class, 'stockLevels'])->name('levels');
                Route::get('/history/{productId}', [StockMutationController::class, 'productHistory'])
                    ->name('history');
                Route::get('/report/warehouse/{warehouseId}', [StockMutationController::class, 'warehouseReport'])
                    ->name('report.warehouse');
            });

            // Merchant Report - Owner/Admin only
            Route::middleware(['can:' . PermissionEnum::VIEW_MERCHANT_REPORTS->value])->group(function () {
                Route::get('/report/merchant/{merchantId}', [StockMutationController::class, 'merchantReport'])
                    ->name('report.merchant');
            });
        });

        // Transaction Monitoring & Reports
        Route::middleware(['can:' . PermissionEnum::VIEW_MERCHANT_REPORTS->value])
            ->prefix('transactions')
            ->name('transactions.')
            ->group(function () {
                Route::get('/', [TransactionController::class, 'index'])->name('index');
                Route::get('/reports', [TransactionController::class, 'reports'])->name('reports');
                Route::get('/{id}', [TransactionController::class, 'show'])->name('show');
            });
    });

    /*
    |--------------------------------------------------------------------------
    | CASHIER / POS MODULE
    |--------------------------------------------------------------------------
    | Create and manage transactions (sales)
    */
    Route::prefix('cashier')
        ->name('cashier.')
        ->middleware(['can:' . PermissionEnum::CREATE_TRANSACTION->value])
        ->group(function () {
            Route::controller(TransactionController::class)->group(function () {
                Route::get('/transactions/create', 'create')->name('transactions.create');
                Route::post('/transactions', 'store')->name('transactions.store');
                Route::get('/transactions/{id}', 'show')->name('transactions.show');
                Route::get('/transactions/{id}/receipt', 'receipt')->name('transactions.receipt');

                // Payment
                Route::post('/transactions/{id}/pay', 'markAsPaid')->name('transactions.pay');

                // Cancel/Void (requires special permission)
                Route::post('/transactions/{id}/cancel', 'cancel')
                    ->middleware('can:' . PermissionEnum::VOID_TRANSACTION->value)
                    ->name('transactions.cancel');
            });
        });
});
