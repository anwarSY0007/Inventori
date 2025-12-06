<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\MerchantService;
use App\Services\ProductService;
use App\Services\TransactionService;
use App\Services\UserService;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected MerchantService $merchantService,
        protected ProductService $productService,
        protected UserService $userService,
        protected WarehouseService $warehouseService
    ) {}
    public function __invoke(Request $request)
    {
        return Inertia::render('dashboard', [
            'stats' => [
                'total_revenue' => $this->transactionService->getTotalRevenue(),
                'total_transactions' => $this->transactionService->getAll([])->total(),
                'total_products' => $this->productService->getAll([])->total(),
                'total_merchants' => $this->merchantService->getAll([])->total(),
                'total_warehouses' => $this->warehouseService->getAll([])->total(),
                'total_users' => $this->userService->getAllUsers([])->count()
            ],
            'recent_transactions' => $this->transactionService->getAll(['limit' => 5]), // Pastikan service handle limit atau gunakan take(5) di frontend
        ]);
    }
}
