<?php

namespace App\Http\Controllers\Web;

use App\Enum\TransactionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionCreateRequest;
use App\Http\Resources\TransactionResource;
use App\Services\MerchantService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected MerchantService $merchantService
    ) {}

    /**
     * Display a listing of transactions
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['merchant_id', 'status', 'start_date', 'end_date']);

        $transactions = $this->transactionService->getAll($filters);

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'filters' => $filters,
            'merchants' => $this->merchantService->getAll(['id', 'name']),
            'statuses' => collect(TransactionEnum::cases())->map(fn($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    /**
     * Show the form for creating a new transaction (POS)
     */
    public function create(): Response
    {
        // Get user's merchant if merchant_owner
        $user = Auth::user();
        $merchant = null;

        if ($user->hasRole('merchant_owner')) {
            $merchant = $this->merchantService->getByKeeperId($user->id, ['id', 'slug', 'name']);
        }

        return Inertia::render('Cashier/CreateTransaction', [
            'merchant' => $merchant ? [
                'id' => $merchant->id,
                'slug' => $merchant->slug,
                'name' => $merchant->name,
                'products' => $merchant->products->map(function ($product) {
                    return [
                        'id' => $product->id,
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
            ] : null,
        ]);
    }

    /**
     * Store a newly created transaction
     */
    public function store(TransactionCreateRequest $request): RedirectResponse
    {
        try {
            $transaction = $this->transactionService->create($request->validated());

            return redirect()
                ->route('cashier.transactions.show', $transaction->id)
                ->with('success', 'Transaksi berhasil dibuat');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transaction
     */
    public function show(string $id): Response
    {
        $transaction = $this->transactionService->getById($id);

        return Inertia::render('Admin/Transactions/Show', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Show transaction receipt/invoice
     */
    public function receipt(string $id): Response
    {
        $transaction = $this->transactionService->getById($id);

        return Inertia::render('Cashier/Receipt', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Mark transaction as paid
     */
    public function markAsPaid(Request $request, string $id): RedirectResponse
    {
        try {
            $this->transactionService->markAsPaid($id, $request->only(['payment_method', 'payment_reference']));

            return back()->with('success', 'Transaksi berhasil ditandai sebagai lunas');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menandai transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel(string $id): RedirectResponse
    {
        try {
            $this->transactionService->cancel($id);

            return back()->with('success', 'Transaksi berhasil dibatalkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Show transaction reports
     */
    public function reports(Request $request): Response
    {
        $filters = $request->only(['merchant_id', 'start_date', 'end_date']);

        $summary = null;
        if (!empty($filters['merchant_id'])) {
            $summary = $this->transactionService->getSummaryByMerchant(
                $filters['merchant_id'],
                $request->only(['start_date', 'end_date'])
            );
        }

        return Inertia::render('Admin/Transactions/Reports', [
            'summary' => $summary,
            'filters' => $filters,
            'merchants' => $this->merchantService->getAll(['id', 'name']),
        ]);
    }
}
