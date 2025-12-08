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

    public function index(): Response
    {
        $field = ['id', 'name', 'thumbnail', 'tagline', 'slug', 'created_at'];
        $categories = $this->categoryService->getAll($field);

        return Inertia::render('Admin/Category/Index', [
            'categories' => CategoryResource::collection($categories),
        ]);
    }
}
