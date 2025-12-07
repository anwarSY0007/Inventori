<?php

use App\Enum\PermissionEnum;
use App\Enum\RolesEnum;
use App\Http\Controllers\Web\{
    CategoryController,
    CustomerController,
    DashboardController,
    MerchantController,
    ProductController,
    StockMutationController,
    TeamMemberController,
    TransactionController,
    UserController,
    WarehouseController
};
use Illuminate\Support\Facades\Route;

// --- GUEST ROUTES ---
Route::middleware('guest')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/register/merchant', 'createMerchant')->name('register.merchant');
        Route::post('/register/merchant', 'storeMerchant')->name('register.merchant.store');
    });
});

// --- AUTHENTICATED ROUTES ---
Route::middleware(['auth', 'verified'])->group(function () {

    // 1. DASHBOARD & PROFILE (Global)
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/switch-team', [UserController::class, 'switchTeam'])->name('switch-team');

    Route::controller(UserController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'profile')->name('show');
        Route::get('/edit', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    // 2. SUPER ADMIN AREA (User Management)
    Route::middleware(['role:' . RolesEnum::SUPER_ADMIN->value])
        ->prefix('admin/users')->name('admin.users.')
        ->controller(UserController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/{id}/assign-role', 'assignRole')->name('assign-role');
            Route::get('/customers/all', 'allCustomers')->name('customers.all');
        });

<<<<<<< HEAD
    // 3. ADMIN / MANAGEMENT MODULE (Resources)
    Route::prefix('admin')->name('admin.')->group(function () {

        // Merchants
        Route::middleware(['can:' . PermissionEnum::MANAGE_MERCHANTS->value])->controller(MerchantController::class)->group(function () {
            Route::get('/merchants/{slug}/products', 'products')->name('merchants.products');
            Route::resource('merchants', MerchantController::class)->parameters(['merchants' => 'slug']);
=======
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
                Route::get('/', [CustomerController::class, 'index'])->name('index');
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
        ->prefix('staff/customers')
        ->name('staff.customers.')
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
            Route::resource('categories', CategoryController::class)
                ->only(['index']);
>>>>>>> 2ca1d8a30384389331fd5119c6c960b8a1dc105f
        });

        // Inventory: Categories, Products, Warehouses
        Route::middleware(['can:' . PermissionEnum::MANAGE_CATEGORIES->value])
            ->resource('categories', CategoryController::class)->only(['index']);

        Route::middleware(['can:' . PermissionEnum::MANAGE_GLOBAL_PRODUCTS->value])
            ->resource('products', ProductController::class)->parameters(['products' => 'slug']);

        Route::middleware(['can:' . PermissionEnum::MANAGE_WAREHOUSES->value])->controller(WarehouseController::class)->group(function () {
            Route::get('/warehouses/{slug}/products', 'products')->name('warehouses.products');
            Route::get('/warehouses/{slug}/transfer', 'transfer')->name('warehouses.transfer');
            Route::resource('warehouses', WarehouseController::class)->parameters(['warehouses' => 'slug']);
        });

        // Stock & Reports
        Route::prefix('stock')->name('stock.')->controller(StockMutationController::class)->group(function () {
            // Warehouse Stock
            Route::middleware(['can:' . PermissionEnum::MANAGE_WAREHOUSE_STOCK->value])->group(function () {
<<<<<<< HEAD
                Route::get('/', 'index')->name('index');
                Route::get('/levels', 'stockLevels')->name('levels');
                Route::get('/history/{productSlug}', 'productHistory')->name('history');
                Route::get('/report/warehouse/{warehouseSlug}', 'warehouseReport')->name('report.warehouse');
=======
                Route::get('/', [StockMutationController::class, 'index'])->name('index');
                Route::get('/levels', [StockMutationController::class, 'stockLevels'])->name('levels');
                Route::get('/history/{productSlug}', [StockMutationController::class, 'productHistory'])
                    ->name('history');
                Route::get('/report/warehouse/{warehouseSlug}', [StockMutationController::class, 'warehouseReport'])
                    ->name('report.warehouse');
            });

            // Merchant Report - Owner/Admin only
            Route::middleware(['can:' . PermissionEnum::VIEW_MERCHANT_REPORTS->value])->group(function () {
                Route::get('/report/merchant/{merchantSlug}', [StockMutationController::class, 'merchantReport'])
                    ->name('report.merchant');
>>>>>>> 2ca1d8a30384389331fd5119c6c960b8a1dc105f
            });
            // Merchant Report
            Route::middleware(['can:' . PermissionEnum::VIEW_MERCHANT_REPORTS->value])
                ->get('/report/merchant/{merchantSlug}', 'merchantReport')->name('report.merchant');
        });

        // Transaction Monitoring
        Route::middleware(['can:' . PermissionEnum::VIEW_MERCHANT_REPORTS->value])
            ->prefix('transactions')->name('transactions.')
            ->controller(TransactionController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/reports', 'reports')->name('reports');
                Route::get('/{id}', 'show')->name('show');
            });
    });

    // 4. TEAM & STAFF MANAGEMENT
    Route::middleware(['role:' . RolesEnum::MERCHANT_OWNER->value . '|' . RolesEnum::ADMIN->value])
        ->prefix('team')->name('team.')->group(function () {

            Route::prefix('members')->name('members.')->controller(TeamMemberController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::put('/{userId}', 'update')->name('update');
                Route::delete('/{userId}', 'destroy')->name('destroy');
            });

            Route::prefix('customers')->name('customers.')->controller(CustomerController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{customerId}', 'show')->name('show');
            });
        });

    // 5. STAFF READ-ONLY ACCESS
    Route::middleware(['role:' . RolesEnum::CASHIER->value . '|' . RolesEnum::WAREHOUSE_STAFF->value . '|' . RolesEnum::MERCHANT_OWNER->value])
        ->prefix('staff/customers')->name('staff.customers.')
        ->controller(CustomerController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{customerId}', 'show')->name('show');
        });

    // 6. CASHIER / POS MODULE
    Route::prefix('cashier')->name('cashier.')
        ->middleware(['can:' . PermissionEnum::CREATE_TRANSACTION->value])
        ->controller(TransactionController::class)->group(function () {
            // Base Transaction
            Route::get('/transactions/create', 'create')->name('transactions.create');
            Route::post('/transactions', 'store')->name('transactions.store');
            Route::get('/transactions/{id}', 'show')->name('transactions.show');
            Route::get('/transactions/{id}/receipt', 'receipt')->name('transactions.receipt');

            // Actions
            Route::post('/transactions/{id}/pay', 'markAsPaid')->name('transactions.pay');
            Route::post('/transactions/{id}/cancel', 'cancel')
                ->middleware('can:' . PermissionEnum::VOID_TRANSACTION->value)
                ->name('transactions.cancel');
        });
});
