<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockLevelResource;
use App\Http\Resources\StockMutationResource;
use App\Http\Resources\WarehouseResource;
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
        ]) ?? [];

        $mutations = $this->stockMutationService->getAll($filters);

        return Inertia::render('Admin/StockMutations/Index', [
            'mutations' => StockMutationResource::collection($mutations),
            'filters' => $filters,
            'products' => ProductResource::collection(
                $this->productService->getAll([], ['id', 'name', 'slug'])
            ),
            'warehouses' => WarehouseResource::collection(
                $this->warehouseService->getAll(['id', 'name', 'slug'])
            ),
            'merchants' => MerchantResource::collection(
                $this->merchantService->getAll(['id', 'name', 'slug'])
            ),
        ]);
    }

    /**
     * Display product stock history
     */
    public function productHistory(Request $request, string $productSlug): Response
    {
        $filters = $request->only(['warehouse_id', 'merchant_id', 'start_date', 'end_date']) ?? [];

        $product = $this->productService->getBySlug($productSlug, ['id', 'name', 'slug', 'thumbnail']);
        $history = $this->stockMutationService->getProductHistory($product->id, $filters);

        return Inertia::render('Admin/StockMutations/ProductHistory', [
            'product' => new ProductResource($product),
            'mutations' => StockMutationResource::collection($history['mutations']),
            'summary' => $history['summary'],
            'filters' => $filters,
            'warehouses' => WarehouseResource::collection(
                $this->warehouseService->getAll(['id', 'name', 'slug'])
            ),
            'merchants' => MerchantResource::collection(
                $this->merchantService->getAll(['id', 'name', 'slug'])
            ),
        ]);
    }

    /**
     * Display warehouse stock report
     */
    public function warehouseReport(Request $request, string $warehouseSlug): Response
    {
        $filters = $request->only(['start_date', 'end_date']) ?? [];

        $warehouse = $this->warehouseService->getBySlug($warehouseSlug, ['id', 'name', 'slug']);
        $report = $this->stockMutationService->getWarehouseReport($warehouse->id, $filters);

        return Inertia::render('Admin/Reports/WarehouseStock', [
            'warehouse' => new WarehouseResource($warehouse),
            'report' => $report,
            'filters' => $filters,
        ]);
    }

    /**
     * Display merchant stock report
     */
    public function merchantReport(Request $request, string $merchantSlug): Response
    {
        $filters = $request->only(['start_date', 'end_date']) ?? [];

        $merchant = $this->merchantService->getBySlug($merchantSlug, ['id', 'name', 'slug']);
        $report = $this->stockMutationService->getMerchantReport($merchant->id, $filters);

        return Inertia::render('Admin/Reports/MerchantStock', [
            'merchant' => new MerchantResource($merchant),
            'report' => $report,
            'filters' => $filters,
        ]);
    }

    /**
     * Show current stock levels across all locations
     */
    public function stockLevels(Request $request): Response
    {
        $filters = $request->only(['product_id', 'warehouse_id', 'merchant_id']) ?? [];
        $products = $this->productService->getAll($filters);

        return Inertia::render('Admin/Reports/StockLevels', [
            'products' => StockLevelResource::collection($products),
            'filters' => $filters,
            'warehouses' => WarehouseResource::collection(
                $this->warehouseService->getAll(['id', 'name', 'slug'])
            ),
            'merchants' => MerchantResource::collection(
                $this->merchantService->getAll(['id', 'name', 'slug'])
            ),
        ]);
    }
}
