<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\MerchantCreateRequest;
use App\Http\Requests\Merchant\MerchantUpdateRequest;
use App\Http\Resources\MerchantResource;
use App\Services\MerchantService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{
    protected $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }
    /**
     * Get all merchants
     */
    public function index(): JsonResponse
    {
        try {
            $merchants = $this->merchantService->getAll();

            return ResponseHelpers::jsonResponse(
                true,
                'Merchants retrieved successfully',
                MerchantResource::collection($merchants),
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve merchants', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve merchants',
                null,
                500
            );
        }
    }

    /**
     * Get merchant detail
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $field = ['id', 'slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'keeper_id', 'created_at', 'updated_at'];
            $merchant = $this->merchantService->getBySlug($slug, $field);

            return ResponseHelpers::jsonResponse(
                true,
                'Merchant detail retrieved',
                new MerchantResource($merchant),
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
            Log::error('Failed to retrieve merchant', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve merchant',
                null,
                500
            );
        }
    }

    /**
     * Get merchant by keeper ID
     */
    public function showByKeeper(): JsonResponse
    {
        $userId = Auth::id();
        try {
            if (!$userId) {
                return ResponseHelpers::jsonResponse(false, 'Unauthorized', null, 401);
            }

            $field = ['id', 'slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'keeper_id', 'created_at', 'updated_at'];

            $merchant = $this->merchantService->getByKeeperId($userId, $field);

            return ResponseHelpers::jsonResponse(
                true,
                'Merchant detail retrieved',
                new MerchantResource($merchant),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Merchant not found for this keeper',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve merchant by keeper', [
                'keeper_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve merchant',
                null,
                500
            );
        }
    }

    /**
     * Create new merchant
     */
    public function store(MerchantCreateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $merchant = $this->merchantService->create($data);

            return ResponseHelpers::jsonResponse(
                true,
                'Merchant created successfully',
                new MerchantResource($merchant),
                201
            );
        } catch (Exception $e) {
            Log::error('Failed to create merchant', [
                'data' => $request->except('thumbnail'),
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to create merchant',
                null,
                500
            );
        }
    }

    /**
     * Update merchant
     */
    public function update(MerchantUpdateRequest $request, string $slug): JsonResponse
    {
        try {
            $data = $request->validated();
            $merchant = $this->merchantService->update($slug, $data);

            return ResponseHelpers::jsonResponse(
                true,
                'Merchant updated successfully',
                new MerchantResource($merchant),
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
            Log::error('Failed to update merchant', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to update merchant',
                null,
                500
            );
        }
    }

    /**
     * Delete merchant
     */
    public function destroy(string $slug): JsonResponse
    {
        try {
            $this->merchantService->delete($slug);

            return ResponseHelpers::jsonResponse(
                true,
                'Merchant deleted successfully',
                null,
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
            Log::error('Failed to delete merchant', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to delete merchant',
                null,
                500
            );
        }
    }
}
