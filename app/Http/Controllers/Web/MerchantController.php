<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\MerchantCreateRequest;
use App\Http\Requests\Merchant\MerchantUpdateRequest;
use App\Http\Resources\MerchantResource;
use App\Services\MerchantService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService,
        protected UserService $userService
    ) {}

    /**
     * Display a listing of merchants
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search']);

        $merchants = $this->merchantService->getAll();

        return Inertia::render('Admin/Merchants/Index', [
            'merchants' => MerchantResource::collection($merchants),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new merchant
     */
    public function create(): Response
    {
        // Get users with merchant_owner role for keeper selection
        $merchantOwners = $this->userService->getAllUsers(['role' => 'merchant_owner'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Admin/Merchants/Create', [
            'merchantOwners' => $merchantOwners,
        ]);
    }

    /**
     * Store a newly created merchant
     */
    public function store(MerchantCreateRequest $request): RedirectResponse
    {
        try {
            $this->merchantService->create($request->validated());

            return redirect()
                ->route('admin.merchants.index')
                ->with('success', 'Merchant berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan merchant: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified merchant
     */
    public function show(string $slug): Response
    {
        $merchant = $this->merchantService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Merchants/Show', [
            'merchant' => new MerchantResource($merchant),
        ]);
    }

    /**
     * Show the form for editing the specified merchant
     */
    public function edit(string $slug): Response
    {
        $merchant = $this->merchantService->getBySlug($slug, ['*']);

        $merchantOwners = $this->userService->getAllUsers(['role' => 'merchant_owner'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Admin/Merchants/Edit', [
            'merchant' => new MerchantResource($merchant),
            'merchantOwners' => $merchantOwners,
        ]);
    }

    /**
     * Update the specified merchant
     */
    public function update(MerchantUpdateRequest $request, string $slug): RedirectResponse
    {
        try {
            $this->merchantService->update($slug, $request->validated());

            return redirect()
                ->route('admin.merchants.index')
                ->with('success', 'Merchant berhasil diperbarui');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui merchant: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified merchant
     */
    public function destroy(string $slug): RedirectResponse
    {
        try {
            $this->merchantService->delete($slug);

            return redirect()
                ->route('admin.merchants.index')
                ->with('success', 'Merchant berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus merchant: ' . $e->getMessage());
        }
    }

    /**
     * Show merchant products management page
     */
    public function products(string $slug): Response
    {
        $merchant = $this->merchantService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Merchants/Products', [
            'merchant' => [
                'id' => $merchant->id,
                'slug' => $merchant->slug,
                'name' => $merchant->name,
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
                            'name' => $product->category?->name,
                        ],
                    ];
                }),
            ],
        ]);
    }
}
