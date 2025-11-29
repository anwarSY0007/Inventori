<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseCreateRequest;
use App\Http\Requests\Warehouse\WarehouseProductRequest;
use App\Http\Requests\Warehouse\WarehouseUpdateRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\WarehouseService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }
    /**
     * Get all warehouses
     */
    public function index(): JsonResponse
    {
        try {
            $warehouses = $this->warehouseService->getAll();

            return ResponseHelpers::jsonResponse(
                true,
                'Warehouses retrieved successfully',
                WarehouseResource::collection($warehouses),
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve warehouses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve warehouses',
                null,
                500
            );
        }
    }

    /**
     * Get warehouse detail
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $field = ['id', 'slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'created_at', 'updated_at'];
            $warehouse = $this->warehouseService->getBySlug($slug, $field);

            return ResponseHelpers::jsonResponse(
                true,
                'Warehouse detail retrieved',
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
            Log::error('Failed to retrieve warehouse', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve warehouse',
                null,
                500
            );
        }
    }

    /**
     * Create new warehouse
     */
    public function store(WarehouseCreateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $warehouse = $this->warehouseService->create($data);

            return ResponseHelpers::jsonResponse(
                true,
                'Warehouse created successfully',
                new WarehouseResource($warehouse),
                201
            );
        } catch (Exception $e) {
            Log::error('Failed to create warehouse', [
                'data' => $request->except('thumbnail'),
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to create warehouse',
                null,
                500
            );
        }
    }

    /**
     * Update warehouse
     */
    public function update(WarehouseUpdateRequest $request, string $slug): JsonResponse
    {
        try {
            $data = $request->validated();
            $warehouse = $this->warehouseService->update($slug, $data);

            return ResponseHelpers::jsonResponse(
                true,
                'Warehouse updated successfully',
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
            Log::error('Failed to update warehouse', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to update warehouse',
                null,
                500
            );
        }
    }

    /**
     * Delete warehouse
     */
    public function destroy(string $slug): JsonResponse
    {
        try {
            $this->warehouseService->delete($slug);

            return ResponseHelpers::jsonResponse(
                true,
                'Warehouse deleted successfully',
                null,
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
            Log::error('Failed to delete warehouse', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to delete warehouse',
                null,
                500
            );
        }
    }
}
