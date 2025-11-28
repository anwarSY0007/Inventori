<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseProductAttachRequest;
use App\Http\Requests\Warehouse\WarehouseProductStockRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\WarehouseService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $products = $request->validated()['products'];
            $warehouse = $this->warehouseService->addProducts($warehouseSlug, $products);

            return ResponseHelpers::jsonResponse(
                true,
                'Products attached to warehouse successfully',
                new WarehouseResource($warehouse),
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
            $stock = $request->validated()['stock'];
            $warehouse = $this->warehouseService->updateProductStock($warehouseSlug, $productId, $stock);

            return ResponseHelpers::jsonResponse(
                true,
                'Product stock updated successfully',
                new WarehouseResource($warehouse),
                200
            );
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
            $warehouse = $this->warehouseService->removeProducts($warehouseSlug, [$productId]);

            return ResponseHelpers::jsonResponse(
                true,
                'Product detached from warehouse successfully',
                new WarehouseResource($warehouse),
                200
            );
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
}
