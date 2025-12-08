<?php

namespace App\Http\Controllers\Web;

use App\Enum\TransactionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\MerchantCreateRequest;
use App\Http\Requests\Merchant\MerchantUpdateRequest;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Models\Transaction;
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
        $filters = $request->only(['search']) ?? [];
        $merchants = $this->merchantService->getAll(['*']);

        $globalStats = [
            'total_transactions' => Transaction::count(),
            'total_revenue' => Transaction::where('status', TransactionEnum::PAID)->sum('grand_total'),
            'total_customers' => Transaction::distinct('phone')->count('phone'),
            'vip_merchants' => Transaction::select('merchant_id')
                ->where('status', TransactionEnum::PAID)
                ->groupBy('merchant_id')
                ->havingRaw('SUM(grand_total) >= ?', [100000000])
                ->get()
                ->count(),
        ];

        return Inertia::render('Admin/Merchants/MerchantPage', [
            'merchants' => MerchantResource::collection($merchants->items())->resolve(),
            'stats' => $globalStats,
            'meta' => [
                'current_page' => $merchants->currentPage(),
                'last_page' => $merchants->lastPage(),
                'per_page' => $merchants->perPage(),
                'total' => $merchants->total(),
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new merchant
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Merchants/Create', [
            'merchantOwners' => $this->getMerchantOwnerOptions(),
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
                ->with('success', 'Merchant created successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create merchant: ' . $e->getMessage());
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

        return Inertia::render('Admin/Merchants/Edit', [
            'merchant' => new MerchantResource($merchant),
            'merchantOwners' => $this->getMerchantOwnerOptions(),
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
                ->with('success', 'Merchant updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update merchant: ' . $e->getMessage());
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
                ->with('success', 'Merchant deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete merchant: ' . $e->getMessage());
        }
    }

    /**
     * Show merchant products management page
     */
    public function products(string $slug): Response
    {
        $merchant = $this->merchantService->getBySlug($slug, ['*']);

        return Inertia::render('Admin/Merchants/Products', [
            'merchant' => new MerchantResource($merchant),
        ]);
    }

    /**
     * Get merchant owners for dropdown (private helper)
     */
    private function getMerchantOwnerOptions(): array
    {
        return $this->userService
            ->getAllUsers(['role' => 'merchant_owner'])
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => "{$user->name} ({$user->email})",
            ])
            ->toArray();
    }
}
