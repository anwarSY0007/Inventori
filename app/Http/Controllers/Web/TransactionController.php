<?php

namespace App\Http\Controllers\Web;

use App\Enum\TransactionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionCreateRequest;
use App\Http\Resources\MerchantResource;
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
        $filters = $request->only(['merchant_id', 'status', 'start_date', 'end_date']) ?? [];
        $transactions = $this->transactionService->getAll($filters);

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'filters' => $filters,
            'merchants' => MerchantResource::collection(
                $this->merchantService->getAll(['id', 'name', 'slug'])
            ),
            'statuses' => $this->getStatusOptions(),
        ]);
    }

    /**
     * Show the form for creating a new transaction (POS)
     */
    public function create(): Response
    {
        $user = Auth::user();
        $merchant = null;

        // Get merchant for merchant_owner role
        if ($user->hasRole('merchant_owner')) {
            $merchant = $this->merchantService->getByKeeperId($user->id, ['id', 'slug', 'name']);
        }

        return Inertia::render('Cashier/CreateTransaction', [
            'merchant' => $merchant ? new MerchantResource($merchant) : null,
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
                ->with('success', 'Transaction created successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create transaction: ' . $e->getMessage());
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
            $paymentData = $request->only(['payment_method', 'payment_reference']) ?? [];
            $this->transactionService->markAsPaid($id, $paymentData);

            return back()->with('success', 'Transaction marked as paid successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark transaction as paid: ' . $e->getMessage());
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel(string $id): RedirectResponse
    {
        try {
            $this->transactionService->cancel($id);

            return back()->with('success', 'Transaction cancelled successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel transaction: ' . $e->getMessage());
        }
    }

    /**
     * Show transaction reports
     */
    public function reports(Request $request): Response
    {
        $filters = $request->only(['merchant_id', 'start_date', 'end_date']) ?? [];

        $summary = !empty($filters['merchant_id'])
            ? $this->transactionService->getSummaryByMerchant(
                $filters['merchant_id'],
                $request->only(['start_date', 'end_date']) ?? []
            )
            : null;

        return Inertia::render('Admin/Transactions/Reports', [
            'summary' => $summary,
            'filters' => $filters,
            'merchants' => MerchantResource::collection(
                $this->merchantService->getAll(['id', 'name', 'slug'])
            ),
        ]);
    }

    /**
     * Get transaction status options for dropdown
     */
    private function getStatusOptions(): array
    {
        return collect(TransactionEnum::cases())
            ->map(fn($status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ])
            ->toArray();
    }
}
