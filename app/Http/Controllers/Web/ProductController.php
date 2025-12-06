<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductCreateRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of products
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category_id', 'is_popular']) ?? [];
        $products = $this->productService->getAll($filters);

        return Inertia::render('Admin/Products/Index', [
            'products' => ProductResource::collection($products),
            'filters' => $filters,
            'categories' => CategoryResource::collection(
                $this->categoryService->getAll(['id', 'name', 'slug'])
            ),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new product
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => CategoryResource::collection(
                $this->categoryService->getAll(['id', 'name', 'slug'])
            ),
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(ProductCreateRequest $request): RedirectResponse
    {
        try {
            $this->productService->create($request->validated());

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Product created successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified product
     */
    public function show(string $slug): Response
    {
        $product = $this->productService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Products/Show', [
            'product' => new ProductResource($product),
        ]);
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(string $slug): Response
    {
        $product = $this->productService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Products/Edit', [
            'product' => new ProductResource($product),
            'categories' => CategoryResource::collection(
                $this->categoryService->getAll(['id', 'name', 'slug'])
            ),
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(ProductUpdateRequest $request, string $slug): RedirectResponse
    {
        try {
            $this->productService->update($slug, $request->validated());

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Product updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(string $slug): RedirectResponse
    {
        try {
            $this->productService->delete($slug);

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Product deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
}
