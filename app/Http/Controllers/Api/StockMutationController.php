<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StockMutationCreateRequest;
use App\Http\Resources\StockMutationResource;
use App\Services\StockMutationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockMutationController extends Controller
{
    public function __construct(
        protected StockMutationService $stockMutationService
    ) {}

    /**
     * Get all stock mutations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'product_id',
                'warehouse_id',
                'merchant_id',
                'type',
                'reference_type',
                'start_date',
                'end_date'
            ]);

            $mutations = $this->stockMutationService->getAll($filters);

            return ResponseHelpers::jsonResponse(
                true,
                'Stock mutations retrieved successfully',
                StockMutationResource::collection($mutations),
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve stock mutations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve stock mutations',
                null,
                500
            );
        }
    }

    /**
     * Get mutation detail
     */
    public function show(string $id): JsonResponse
    {
        try {
            $mutation = $this->stockMutationService->getById($id);

            return ResponseHelpers::jsonResponse(
                true,
                'Stock mutation detail retrieved',
                new StockMutationResource($mutation),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Stock mutation not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve stock mutation', [
                'mutation_id' => $id,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve stock mutation',
                null,
                500
            );
        }
    }

    /**
     * Create manual stock mutation (adjustment)
     */
    public function store(StockMutationCreateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $mutation = $this->stockMutationService->recordMutation($data);

            return ResponseHelpers::jsonResponse(
                true,
                'Stock mutation recorded successfully',
                new StockMutationResource($mutation),
                201
            );
        } catch (Exception $e) {
            Log::error('Failed to create stock mutation', [
                'data' => $request->validated(),
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to record stock mutation',
                null,
                500
            );
        }
    }

    /**
     * Get stock mutations by product
     */
    public function byProduct(string $productId, Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $mutations = $this->stockMutationService->getByProduct($productId, $limit);

            return ResponseHelpers::jsonResponse(
                true,
                'Product stock mutations retrieved successfully',
                StockMutationResource::collection($mutations),
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve product stock mutations', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve product stock mutations',
                null,
                500
            );
        }
    }

    /**
     * Get stock mutations by reference (e.g., transaction)
     */
    public function byReference(Request $request): JsonResponse
    {
        try {
            $referenceType = $request->input('reference_type');
            $referenceId = $request->input('reference_id');

            if (!$referenceType || !$referenceId) {
                return ResponseHelpers::jsonResponse(
                    false,
                    'reference_type and reference_id are required',
                    null,
                    422
                );
            }

            $mutations = $this->stockMutationService->getByReference($referenceType, $referenceId);

            return ResponseHelpers::jsonResponse(
                true,
                'Stock mutations by reference retrieved successfully',
                StockMutationResource::collection($mutations),
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve stock mutations by reference', [
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve stock mutations',
                null,
                500
            );
        }
    }

    /**
     * Get product stock history & summary
     */
    public function productHistory(Request $request, string $productId): JsonResponse
    {
        try {
            $filters = $request->only([
                'warehouse_id',
                'merchant_id',
                'start_date',
                'end_date'
            ]);

            $history = $this->stockMutationService->getProductHistory($productId, $filters);

            return ResponseHelpers::jsonResponse(
                true,
                'Product stock history retrieved successfully',
                [
                    'mutations' => StockMutationResource::collection($history['mutations']),
                    'summary' => $history['summary']
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve product stock history', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve product stock history',
                null,
                500
            );
        }
    }

    /**
     * Get warehouse stock report
     */
    public function warehouseReport(Request $request, string $warehouseId): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);
            $report = $this->stockMutationService->getWarehouseReport($warehouseId, $filters);

            return ResponseHelpers::jsonResponse(
                true,
                'Warehouse stock report retrieved successfully',
                $report,
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve warehouse report', [
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve warehouse report',
                null,
                500
            );
        }
    }

    /**
     * Get merchant stock report
     */
    public function merchantReport(Request $request, string $merchantId): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);
            $report = $this->stockMutationService->getMerchantReport($merchantId, $filters);

            return ResponseHelpers::jsonResponse(
                true,
                'Merchant stock report retrieved successfully',
                $report,
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve merchant report', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve merchant report',
                null,
                500
            );
        }
    }

    /**
     * Get current stock
     */
    public function currentStock(Request $request, string $productId): JsonResponse
    {
        try {
            $warehouseId = $request->input('warehouse_id');
            $merchantId = $request->input('merchant_id');

            $stock = $this->stockMutationService->getCurrentStock(
                $productId,
                $warehouseId,
                $merchantId
            );

            return ResponseHelpers::jsonResponse(
                true,
                'Current stock retrieved successfully',
                [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'merchant_id' => $merchantId,
                    'current_stock' => $stock
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve current stock', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve current stock',
                null,
                500
            );
        }
    }
}
