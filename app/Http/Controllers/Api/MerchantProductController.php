<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\MerchantProductAttachRequest;
use App\Http\Requests\Merchant\MerchantProductStockRequest;
use App\Http\Resources\MerchantResource;
use App\Services\MerchantProductService;
use App\Services\MerchantService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MerchantProductController extends Controller
{
    protected $merchantService;
    protected $merchantProductService;

    public function __construct(
        MerchantService $merchantService,
        MerchantProductService $merchantProductService
    ) {
        $this->merchantService = $merchantService;
        $this->merchantProductService = $merchantProductService;
    }

    /**
     * Get all products in specific merchant
     */
    public function getProducts(string $merchantSlug): JsonResponse
    {
        try {
            $merchant = $this->merchantService->getBySlug($merchantSlug, ['id', 'slug', 'name', 'keeper_id']);

            return ResponseHelpers::jsonResponse(
                true,
                'Merchant products retrieved successfully',
                [
                    'merchant' => [
                        'id' => $merchant->id,
                        'slug' => $merchant->slug,
                        'name' => $merchant->name,
                        'keeper' => [
                            'id' => $merchant->keeper?->id,
                            'name' => $merchant->keeper?->name,
                        ]
                    ],
                    'products' => $merchant->products->map(function ($product) {
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
                    'total_products' => $merchant->products->count()
                ],
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Merchant not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to get merchant products', [
                'merchant_slug' => $merchantSlug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve merchant products',
                null,
                500
            );
        }
    }

    /**
     * Attach products to merchant (manual, tanpa warehouse)
     */
    public function attachProduct(MerchantProductAttachRequest $request, string $merchantSlug): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $merchantSlug) {
                $products = $request->validated()['products'];
                $merchant = $this->merchantService->addProducts($merchantSlug, $products);

                return ResponseHelpers::jsonResponse(
                    true,
                    'Products attached to merchant successfully',
                    new MerchantResource($merchant),
                    200
                );
            });
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Merchant not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to attach products', [
                'merchant_slug' => $merchantSlug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to attach products to merchant',
                null,
                500
            );
        }
    }

    /**
     * Update product stock in merchant (manual adjustment)
     */
    public function updateStock(
        MerchantProductStockRequest $request,
        string $merchantSlug,
        string $productId
    ): JsonResponse {
        try {
            return DB::transaction(function () use ($request, $merchantSlug, $productId) {
                $stock = $request->validated()['stock'];
                $merchant = $this->merchantService->updateProductStock($merchantSlug, $productId, $stock);

                return ResponseHelpers::jsonResponse(
                    true,
                    'Product stock updated successfully',
                    new MerchantResource($merchant),
                    200
                );
            });
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Merchant or product not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to update product stock', [
                'merchant_slug' => $merchantSlug,
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
     * Detach product from merchant
     */
    public function detachProduct(string $merchantSlug, string $productId): JsonResponse
    {
        try {
            return DB::transaction(function () use ($merchantSlug, $productId) {
                $merchant = $this->merchantService->removeProducts($merchantSlug, [$productId]);

                return ResponseHelpers::jsonResponse(
                    true,
                    'Product detached from merchant successfully',
                    new MerchantResource($merchant),
                    200
                );
            });
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Merchant or product not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to detach product', [
                'merchant_slug' => $merchantSlug,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to detach product from merchant',
                null,
                500
            );
        }
    }

    /**
     * 🆕 Return product dari merchant ke warehouse
     * POST /api/merchants/{slug}/products/{productId}/return
     */
    public function returnToWarehouse(
        Request $request,
        string $merchantSlug,
        string $productId
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'warehouse_id' => 'required|string|exists:warehouses,id',
                'amount' => 'required|integer|min:1',
                'note' => 'nullable|string|max:500',
            ]);

            $merchant = $this->merchantService->getBySlug($merchantSlug, ['id', 'name']);

            $result = $this->merchantProductService->returnProductToWarehouse([
                'merchant_id' => $merchant->id,
                'warehouse_id' => $validated['warehouse_id'],
                'product_id' => $productId,
                'stock' => $validated['amount'],
            ]);

            return ResponseHelpers::jsonResponse(
                true,
                'Stock returned to warehouse successfully',
                [
                    'merchant' => [
                        'id' => $merchant->id,
                        'name' => $merchant->name,
                        'stock_after' => $result['merchant_stock_after'],
                    ],
                    'return' => $result,
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
                'Merchant not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to return stock to warehouse', [
                'merchant' => $merchantSlug,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to return stock',
                null,
                500
            );
        }
    }

    /**
     * 🆕 Transfer product antar merchant
     * POST /api/merchants/{slug}/products/{productId}/transfer
     */
    public function transferToMerchant(
        Request $request,
        string $merchantSlug,
        string $productId
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'target_merchant_id' => 'required|string|exists:merchants,id',
                'amount' => 'required|integer|min:1',
                'note' => 'nullable|string|max:500',
            ]);

            $sourceMerchant = $this->merchantService->getBySlug($merchantSlug, ['id', 'name']);

            // Validasi tidak transfer ke diri sendiri
            if ($sourceMerchant->id === $validated['target_merchant_id']) {
                return ResponseHelpers::jsonResponse(
                    false,
                    'Cannot transfer to the same merchant',
                    null,
                    422
                );
            }

            $result = $this->merchantProductService->transferBetweenMerchants([
                'source_merchant_id' => $sourceMerchant->id,
                'target_merchant_id' => $validated['target_merchant_id'],
                'product_id' => $productId,
                'stock' => $validated['amount'],
            ]);

            return ResponseHelpers::jsonResponse(
                true,
                'Stock transferred to merchant successfully',
                [
                    'source_merchant' => [
                        'id' => $sourceMerchant->id,
                        'name' => $sourceMerchant->name,
                        'stock_after' => $result['source_stock_after'],
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
                'Merchant not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to transfer stock between merchants', [
                'source_merchant' => $merchantSlug,
                'product_id' => $productId,
                'error' => $e->getMessage()
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
     * 🆕 Get stock movement history untuk merchant
     * GET /api/merchants/{slug}/products/{productId}/movements
     */
    public function getMovementHistory(
        Request $request,
        string $merchantSlug,
        string $productId
    ): JsonResponse {
        try {
            $history = $this->merchantProductService->getStockMovementHistory(
                $this->merchantService->getBySlug($merchantSlug, ['id'])->id,
                $productId
            );

            return ResponseHelpers::jsonResponse(
                true,
                'Stock movement history retrieved successfully',
                $history,
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
                'Merchant not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to get movement history', [
                'merchant' => $merchantSlug,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve movement history',
                null,
                500
            );
        }
    }
}
