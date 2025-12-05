<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseCreateRequest;
use App\Http\Requests\Warehouse\WarehouseUpdateRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService
    ) {}

    /**
     * Display a listing of warehouses
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search']);

        $warehouses = $this->warehouseService->getAll();

        return Inertia::render('Admin/Warehouses/Index', [
            'warehouses' => WarehouseResource::collection($warehouses),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new warehouse
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Warehouses/Create');
    }

    /**
     * Store a newly created warehouse
     */
    public function store(WarehouseCreateRequest $request): RedirectResponse
    {
        try {
            $this->warehouseService->create($request->validated());

            return redirect()
                ->route('admin.warehouses.index')
                ->with('success', 'Warehouse berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan warehouse: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified warehouse
     */
    public function show(string $slug): Response
    {
        $warehouse = $this->warehouseService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Warehouses/Show', [
            'warehouse' => new WarehouseResource($warehouse),
        ]);
    }

    /**
     * Show the form for editing the specified warehouse
     */
    public function edit(string $slug): Response
    {
        $warehouse = $this->warehouseService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Warehouses/Edit', [
            'warehouse' => new WarehouseResource($warehouse),
        ]);
    }

    /**
     * Update the specified warehouse
     */
    public function update(WarehouseUpdateRequest $request, string $slug): RedirectResponse
    {
        try {
            $this->warehouseService->update($slug, $request->validated());

            return redirect()
                ->route('admin.warehouses.index')
                ->with('success', 'Warehouse berhasil diperbarui');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui warehouse: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified warehouse
     */
    public function destroy(string $slug): RedirectResponse
    {
        try {
            $this->warehouseService->delete($slug);

            return redirect()
                ->route('admin.warehouses.index')
                ->with('success', 'Warehouse berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus warehouse: ' . $e->getMessage());
        }
    }

    /**
     * Show warehouse products management page
     */
    public function products(string $slug): Response
    {
        $warehouse = $this->warehouseService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Warehouses/Products', [
            'warehouse' => [
                'id' => $warehouse->id,
                'slug' => $warehouse->slug,
                'name' => $warehouse->name,
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
                            'name' => $product->category?->name,
                        ],
                    ];
                }),
            ],
        ]);
    }

    /**
     * Show warehouse stock transfer page
     */
    public function transfer(string $slug): Response
    {
        $warehouse = $this->warehouseService->getBySlug($slug, ['id', 'slug', 'name']);

        return Inertia::render('Admin/Warehouses/Transfer', [
            'warehouse' => [
                'id' => $warehouse->id,
                'slug' => $warehouse->slug,
                'name' => $warehouse->name,
            ],
        ]);
    }
}
