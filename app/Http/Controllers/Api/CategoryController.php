<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\CategoryCreateRequest;
use App\Http\Requests\Categories\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAll();

            return ResponseHelpers::jsonResponse(
                true,
                'Categories retrieved successfully',
                CategoryResource::collection($categories),
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve categories',
                null,
                500
            );
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $field = ['id', 'slug', 'name', 'thumbnail', 'tagline', 'created_at', 'updated_at'];
            $category = $this->categoryService->getBySlug($slug, $field);

            return ResponseHelpers::jsonResponse(
                true,
                'Category detail retrieved',
                new CategoryResource($category),
                200
            );
        } catch (ModelNotFoundException) {
            // Catch spesifik ModelNotFoundException
            return ResponseHelpers::jsonResponse(
                false,
                'Category not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve category', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve category',
                null,
                500
            );
        }
    }

    public function store(CategoryCreateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $category = $this->categoryService->create($data);

            return ResponseHelpers::jsonResponse(
                true,
                'Category created successfully',
                new CategoryResource($category),
                201
            );
        } catch (Exception $e) {
            Log::error('Failed to create category', [
                'data' => $request->validated(),
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to create category',
                null,
                500
            );
        }
    }

    public function update(CategoryUpdateRequest $request, string $slug): JsonResponse
    {
        try {
            $data = $request->validated();
            $category = $this->categoryService->update($slug, $data);

            return ResponseHelpers::jsonResponse(
                true,
                'Category updated successfully',
                new CategoryResource($category),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Category not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to update category', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to update category',
                null,
                500
            );
        }
    }

    public function destroy(string $slug): JsonResponse
    {
        try {
            $this->categoryService->delete($slug);

            return ResponseHelpers::jsonResponse(
                true,
                'Category deleted successfully',
                null,
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Category not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to delete category', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to delete category',
                null,
                500
            );
        }
    }
}
