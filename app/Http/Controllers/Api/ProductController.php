<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductCreateRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Bisa tambah filter query param disini, misal ?category_id=...
            $filters = $request->only(['category_id', 'is_popular']);

            $products = $this->productService->getAll($filters);

            return ResponseHelpers::jsonResponse(
                true,
                'Products retrieved successfully',
                ProductResource::collection($products),
                200
            );
        } catch (Exception $e) {
            Log::error('Fetch products error', ['error' => $e->getMessage()]);
            return ResponseHelpers::jsonResponse(false, 'Server Error', null, 500);
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $field = ['*'];
            $product = $this->productService->getBySlug($slug, $field);
            return ResponseHelpers::jsonResponse(
                true,
                'Product detail retrieved',
                new ProductResource($product),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(false, 'Product not found', null, 404);
        } catch (Exception $e) {
            return ResponseHelpers::jsonResponse(false, 'Server Error', null, 500);
        }
    }

    public function store(ProductCreateRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->create($request->validated());
            return ResponseHelpers::jsonResponse(
                true,
                'Product created successfully',
                new ProductResource($product),
                201
            );
        } catch (Exception $e) {
            Log::error('Create product error', ['error' => $e->getMessage()]);
            return ResponseHelpers::jsonResponse(false, 'Server Error', null, 500);
        }
    }

    public function update(ProductUpdateRequest $request, string $slug): JsonResponse
    {
        try {
            $product = $this->productService->update($slug, $request->validated());
            return ResponseHelpers::jsonResponse(
                true,
                'Product updated successfully',
                new ProductResource($product),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(false, 'Product not found', null, 404);
        } catch (Exception $e) {
            Log::error('Update product error', ['error' => $e->getMessage()]);
            return ResponseHelpers::jsonResponse(false, 'Server Error', null, 500);
        }
    }

    public function destroy(string $slug): JsonResponse
    {
        try {
            $this->productService->delete($slug);
            return ResponseHelpers::jsonResponse(true, 'Product deleted successfully', null, 200);
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(false, 'Product not found', null, 404);
        } catch (Exception $e) {
            return ResponseHelpers::jsonResponse(false, 'Server Error', null, 500);
        }
    }
}
