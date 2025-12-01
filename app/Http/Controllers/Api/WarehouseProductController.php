<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseProductAttachRequest;
use App\Http\Requests\Warehouse\WarehouseProductStockRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\MerchantProductService;
use App\Services\WarehouseService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WarehouseProductController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Attach single or multiple products to warehouse
     */
    public function attachProduct(WarehouseProductAttachRequest $request, string $warehouseSlug): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $warehouseSlug) {
                $products = $request->validated()['products'];
                $warehouse = $this->warehouseService->addProducts($warehouseSlug, $products);

                return ResponseHelpers::jsonResponse(
                    true,
                    'Products attached to warehouse successfully',
                    new WarehouseResource($warehouse),
                    200
                );
            });
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Warehouse not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to attach products', [
                'warehouse_slug' => $warehouseSlug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to attach products to warehouse',
                null,
                500
            );
        }
    }

    /**
     * Update stock for specific product in warehouse
     */
    public function updateStock(WarehouseProductStockRequest $request, string $warehouseSlug, string $productId): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $warehouseSlug, $productId) {
                $stock = $request->validated()['stock'];
                $warehouse = $this->warehouseService->updateProductStock($warehouseSlug, $productId, $stock);

                return ResponseHelpers::jsonResponse(
                    true,
                    'Product stock updated successfully',
                    new WarehouseResource($warehouse),
                    200
                );
            });
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Warehouse or product not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to update product stock', [
                'warehouse_slug' => $warehouseSlug,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to update product stock',
                null,
                500
            );
        }
    }

    /**
     * Detach products from warehouse
     */
    public function detachProduct(string $warehouseSlug, string $productId): JsonResponse
    {
        try {
            return DB::transaction(function () use ($warehouseSlug, $productId) {
                $warehouse = $this->warehouseService->removeProducts($warehouseSlug, [$productId]);

                return ResponseHelpers::jsonResponse(
                    true,
                    'Product detached from warehouse successfully',
                    new WarehouseResource($warehouse),
                    200
                );
            });
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Warehouse or product not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to detach product', [
                'warehouse_slug' => $warehouseSlug,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to detach product from warehouse',
                null,
                500
            );
        }
    }

    /**
     * Get all products in specific warehouse
     */
    public function getProducts(string $warehouseSlug): JsonResponse
    {
        try {
            $warehouse = $this->warehouseService->getBySlug($warehouseSlug, ['id', 'slug', 'name']);

            return ResponseHelpers::jsonResponse(
                true,
                'Warehouse products retrieved successfully',
                [
                    'warehouse' => [
                        'id' => $warehouse->id,
                        'slug' => $warehouse->slug,
                        'name' => $warehouse->name,
                    ],
                    'products' => $warehouse->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'slug' => $product->slug,
                            'name' => $product->name,
                            'thumbnail' => $product->thumbnail,
                            'price' => $product->price,
                            'stock' => $product->pivot->stock,
                            'category' => [
                                'id' => $product->category?->id,
                                'slug' => $product->category?->slug,
                                'name' => $product->category?->name,
                            ]
                        ];
                    }),
                    'total_products' => $warehouse->products->count()
                ],
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Warehouse not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to get warehouse products', [
                'warehouse_slug' => $warehouseSlug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve warehouse products',
                null,
                500
            );
        }
    }

    /**
     * Transfer product dari warehouse ke merchant
     * POST /api/warehouses/{slug}/products/{productId}/transfer
     */
    public function transferToMerchant(
        Request $request,
        string $warehouseSlug,
        string $productId
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'merchant_id' => 'required|string|exists:merchants,id',
                'amount' => 'required|integer|min:1',
                'note' => 'nullable|string|max:500',
            ]);

            $warehouse = $this->warehouseService->getBySlug($warehouseSlug, ['id', 'name']);

            $merchantProductService = app(MerchantProductService::class);

            $result = $merchantProductService->assignProductToMerchant([
                'warehouse_id' => $warehouse->id,
                'merchant_id' => $validated['merchant_id'],
                'product_id' => $productId,
                'stock' => $validated['amount'],
            ]);

            return ResponseHelpers::jsonResponse(
                true,
                'Stock transferred to merchant successfully',
                [
                    'warehouse' => [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'stock_after' => $result['warehouse_stock_after'],
                    ],
                    'transfer' => $result,
                ],
                200
            );
        } catch (ValidationException $e) {
            return ResponseHelpers::jsonResponse(
                false,
                $e->getMessage(),
                $e->errors(),
                422
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Warehouse not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to transfer stock to merchant', [
                'warehouse' => $warehouseSlug,
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to transfer stock',
                null,
                500
            );
        }
    }

    /**
     * Get transfer history untuk specific product di warehouse
     * GET /api/warehouses/{slug}/products/{productId}/transfers
     */
    public function getTransferHistory(
        Request $request,
        string $warehouseSlug,
        string $productId
    ): JsonResponse {
        try {
            $warehouse = $this->warehouseService->getBySlug($warehouseSlug, ['id', 'name']);

            $filters = [
                'warehouse_id' => $warehouse->id,
                'product_id' => $productId,
            ];

            if ($request->has('start_date')) {
                $filters['start_date'] = $request->start_date;
            }

            if ($request->has('end_date')) {
                $filters['end_date'] = $request->end_date;
            }

            $stockMutationService = app(\App\Services\StockMutationService::class);
            $history = $stockMutationService->getProductHistory($productId, $filters);

            return ResponseHelpers::jsonResponse(
                true,
                'Transfer history retrieved successfully',
                [
                    'warehouse' => [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                    ],
                    'product_id' => $productId,
                    'history' => $history,
                ],
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Warehouse not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to get transfer history', [
                'warehouse' => $warehouseSlug,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve transfer history',
                null,
                500
            );
        }
    }

    /**
     * 🆕 Batch transfer multiple products to merchant
     * POST /api/warehouses/{slug}/products/batch-transfer
     */
    public function batchTransferToMerchant(
        Request $request,
        string $warehouseSlug
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'merchant_id' => 'required|string|exists:merchants,id',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|string|exists:products,id',
                'products.*.amount' => 'required|integer|min:1',
            ]);

            $warehouse = $this->warehouseService->getBySlug($warehouseSlug, ['id', 'name']);
            $merchantProductService = app(MerchantProductService::class);

            return DB::transaction(function () use ($validated, $warehouse, $merchantProductService) {
                $results = [];
                $totalTransferred = 0;

                foreach ($validated['products'] as $item) {
                    $result = $merchantProductService->assignProductToMerchant([
                        'warehouse_id' => $warehouse->id,
                        'merchant_id' => $validated['merchant_id'],
                        'product_id' => $item['product_id'],
                        'stock' => $item['amount'],
                    ]);

                    $results[] = [
                        'product_id' => $item['product_id'],
                        'amount' => $item['amount'],
                        'warehouse_stock_after' => $result['warehouse_stock_after'],
                    ];

                    $totalTransferred += $item['amount'];
                }

                return ResponseHelpers::jsonResponse(
                    true,
                    'Batch transfer completed successfully',
                    [
                        'warehouse_id' => $warehouse->id,
                        'merchant_id' => $validated['merchant_id'],
                        'total_products' => count($results),
                        'total_transferred' => $totalTransferred,
                        'details' => $results,
                    ],
                    200
                );
            });
        } catch (ValidationException $e) {
            return ResponseHelpers::jsonResponse(
                false,
                $e->getMessage(),
                $e->errors(),
                422
            );
        } catch (Exception $e) {
            Log::error('Failed to batch transfer', [
                'warehouse' => $warehouseSlug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to complete batch transfer',
                null,
                500
            );
        }
    }
}
