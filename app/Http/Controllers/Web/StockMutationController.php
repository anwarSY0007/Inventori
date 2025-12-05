<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockLevelResource;
use App\Http\Resources\StockMutationResource;
use App\Services\MerchantService;
use App\Services\ProductService;
use App\Services\StockMutationService;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMutationController extends Controller
{
    public function __construct(
        protected StockMutationService $stockMutationService,
        protected ProductService $productService,
        protected WarehouseService $warehouseService,
        protected MerchantService $merchantService
    ) {}

    /**
     * Display a listing of stock mutations
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'product_id',
            'warehouse_id',
            'merchant_id',
            'type',
            'start_date',
            'end_date'
        ]);

        $mutations = $this->stockMutationService->getAll($filters);

        return Inertia::render('Admin/StockMutations/Index', [
            'mutations' => StockMutationResource::collection($mutations),
            'filters' => $filters,
            'products' => $this->productService->getAll([], ['id', 'name']),
            'warehouses' => $this->warehouseService->getAll(['id', 'name']),
            'merchants' => $this->merchantService->getAll(['id', 'name']),
        ]);
    }

    /**
     * Display product stock history
     */
    public function productHistory(Request $request, string $productId): Response
    {
        $filters = $request->only(['warehouse_id', 'merchant_id', 'start_date', 'end_date']);

        $history = $this->stockMutationService->getProductHistory($productId, $filters);

        $product = $this->productService->getBySlug($productId, ['id', 'name', 'slug', 'thumbnail']);

        return Inertia::render('Admin/StockMutations/ProductHistory', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail' => $product->thumbnail,
            ],
            'mutations' => StockMutationResource::collection($history['mutations']),
            'summary' => $history['summary'],
            'filters' => $filters,
            'warehouses' => $this->warehouseService->getAll(['id', 'name']),
            'merchants' => $this->merchantService->getAll(['id', 'name']),
        ]);
    }

    /**
     * Display warehouse stock report
     */
    public function warehouseReport(Request $request, string $warehouseId): Response
    {
        $filters = $request->only(['start_date', 'end_date']);

        $report = $this->stockMutationService->getWarehouseReport($warehouseId, $filters);

        $warehouse = $this->warehouseService->getById($warehouseId, ['id', 'name', 'slug']);

        return Inertia::render('Admin/Reports/WarehouseStock', [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'slug' => $warehouse->slug,
            ],
            'report' => $report,
            'filters' => $filters,
        ]);
    }

    /**
     * Display merchant stock report
     */
    public function merchantReport(Request $request, string $merchantId): Response
    {
        $filters = $request->only(['start_date', 'end_date']);

        $report = $this->stockMutationService->getMerchantReport($merchantId, $filters);

        $merchant = $this->merchantService->getById($merchantId, ['id', 'name', 'slug']);

        return Inertia::render('Admin/Reports/MerchantStock', [
            'merchant' => [
                'id' => $merchant->id,
                'name' => $merchant->name,
                'slug' => $merchant->slug,
            ],
            'report' => $report,
            'filters' => $filters,
        ]);
    }

    /**
     * Show current stock levels across all locations
     */
    public function stockLevels(Request $request): Response
    {
        $filters = $request->only(['product_id', 'warehouse_id', 'merchant_id']);

        $products = $this->productService->getAll($filters);

        return Inertia::render('Admin/Reports/StockLevels', [
            'products' => StockLevelResource::collection($products),
            'filters' => $filters,
            'warehouses' => $this->warehouseService->getAll(['id', 'name']),
            'merchants' => $this->merchantService->getAll(['id', 'name']),
        ]);
    }
}
