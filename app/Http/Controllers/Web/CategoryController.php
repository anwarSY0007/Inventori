<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): Response
    {
        $field = ['id', 'name', 'thumbnail', 'tagline', 'slug', 'created_at'];
        $categories = $this->categoryService->getAll($field);
        $filters = $request->only(['search']);

        return Inertia::render('Admin/Category/CategoryPage', [
            'categories' => CategoryResource::collection($categories->items())->resolve(),
            'filters' => $filters,
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }
}
