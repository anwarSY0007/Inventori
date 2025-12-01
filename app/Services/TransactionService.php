<?php

namespace App\Services;

use App\Enum\TransactionEnum;
use App\Models\Transaction;
use App\Repositories\MerchantRepository;
use App\Repositories\TransactionRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected MerchantRepository $merchantRepository,
        protected StockMutationService $stockMutationService
    ) {}

    /**
     * Get all transactions
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->transactionRepository->getAllTransaction($filters);
    }

    /**
     * Get transaction by ID
     */
    public function getById(string $id): Transaction
    {
        return $this->transactionRepository->getTransactionById($id);
    }

    /**
     * Get transaction by slug
     */
    public function getBySlug(string $slug): Transaction
    {
        return $this->transactionRepository->getTransactionBySlug($slug);
    }

    /**
     * Get transaction by invoice code
     */
    public function getByInvoice(string $invoiceCode): Transaction
    {
        return $this->transactionRepository->getTransactionByInvoice($invoiceCode);
    }

    /**
     * Create new transaction (checkout)
     */
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Validasi merchant
            $merchant = $this->merchantRepository->getMerchantById($data['merchant_id'], ['*']);
            if (!$merchant->is_active) throw new Exception("Merchant sedang tutup.");

            $productIds = array_column($data['products'], 'product_id');

            $merchantProducts = $merchant->products()
                ->whereIn('products.id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Generate invoice code
            $invoiceCode = $this->transactionRepository->generateInvoiceCode($data['merchant_id']);

            // Prepare transaction data
            $transactionData = [
                'invoice_code' => $invoiceCode,
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'],
                'sub_total' => 0,
                'tax_total' => $data['tax_total'] ?? 0,
                'grand_total' => 0,
                'status' => TransactionEnum::PENDING,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'merchant_id' => $data['merchant_id'],
                'cashier_id' => Auth::id(),
            ];

            // Create transaction
            $transaction = $this->transactionRepository->createTransaction($transactionData);

            // Process products & calculate totals
            $subTotal = 0;
            $productsToAttach = [];

            foreach ($data['products'] as $item) {
                $productId = $item['product_id'];
                $qty = $item['qty'];
                $merchantProduct = $merchantProducts->get($productId);

                if (!$merchantProduct) {
                    throw ValidationException::withMessages([
                        'products' => ["Produk {$productId} tidak tersedia di merchant ini"]
                    ]);
                }
                if ($merchantProduct->pivot->stock < $qty) {
                    throw ValidationException::withMessages([
                        'products' => ["Stok produk {$merchantProduct->name} tidak mencukupi. Tersedia: {$merchantProduct->pivot->stock}"]
                    ]);
                }

                $price = $merchantProduct->price;
                $subTotalItem = $price * $qty;

                $productsToAttach[$productId] = [
                    'qty' => $qty,
                    'price' => $price,
                    'sub_total' => $subTotalItem
                ];

                $subTotal += $subTotalItem;
                $newStock = $merchantProduct->pivot->stock - $qty;
                $this->merchantRepository->updateProductStock($merchant, $productId, $newStock);
                $this->stockMutationService->recordMutation([
                    'product_id' => $productId,
                    'merchant_id' => $merchant->id,
                    'type' => 'out',
                    'amount' => $qty,
                    'current_stock' => $newStock,
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                    'note' => "Penjualan - Invoice: {$invoiceCode}",
                    'created_by' => Auth::id()
                ]);
            }

            // Attach products to transaction
            $this->transactionRepository->attachProducts($transaction, $productsToAttach);

            // Update totals
            $grandTotal = $subTotal + $transactionData['tax_total'];

            $this->transactionRepository->updateTransaction($transaction, [
                'sub_total' => $subTotal,
                'grand_total' => $grandTotal
            ]);

            return $transaction->fresh(['merchant', 'cashier', 'transactionProducts.product']);
        });
    }

    /**
     * Update transaction status (mark as paid, failed, cancelled)
     */
    public function updateStatus(string $transactionId, string $status): Transaction
    {
        return DB::transaction(function () use ($transactionId, $status) {
            $transaction = Transaction::lockForUpdate()->find($transactionId);

            if (!$transaction) {
                throw new Exception("Transaksi tidak ditemukan.");
            }

            $targetStatus = TransactionEnum::from($status);
            $currentStatus = $transaction->status;

            // [New Rule] Validasi Perubahan Status (State Machine)
            $this->validateStatusTransition($currentStatus, $targetStatus);

            // [Compensating] Logika Kompensasi saat Cancel
            if ($targetStatus === TransactionEnum::CANCELLED) {
                $this->handleCancellationCompensation($transaction);
            }

            return $this->transactionRepository->updateStatus($transaction, $targetStatus);
        });
    }

    /**
     * Cancel transaction & restore stock
     */
    public function cancel(string $transactionId): Transaction
    {
        return $this->updateStatus($transactionId, TransactionEnum::CANCELLED->value);
    }

    /**
     * Mark transaction as paid
     */
    public function markAsPaid(string $transactionId, array $paymentData = []): Transaction
    {
        return DB::transaction(function () use ($transactionId, $paymentData) {
            $transaction = Transaction::lockForUpdate()->find($transactionId);

            // [New Rule] Tidak bisa bayar jika sudah cancel atau paid
            if ($transaction->status !== TransactionEnum::PENDING) {
                throw new Exception("Transaksi tidak dapat dibayar karena status saat ini: {$transaction->status->value}");
            }

            $updateData = [
                'status' => TransactionEnum::PAID,
                'paid_at' => now(),
            ];

            if (!empty($paymentData['payment_method'])) {
                $updateData['payment_method'] = $paymentData['payment_method'];
            }
            if (!empty($paymentData['payment_reference'])) {
                $updateData['payment_reference'] = $paymentData['payment_reference'];
            }

            return $this->transactionRepository->updateTransaction($transaction, $updateData);
        });
    }

    /**
     * [New Rule] State Transition Validation
     * Mencegah perubahan status yang tidak logis
     */
    private function validateStatusTransition(TransactionEnum $current, TransactionEnum $target): void
    {
        // 1. Jika sudah Final (CANCELLED/PAID), tidak boleh berubah lagi (Immutable terminal state)
        //    Kecuali ada fitur 'Refund' (PAID -> CANCELLED) yang kita izinkan di bawah.
        if ($current === TransactionEnum::CANCELLED) {
            throw new Exception("Transaksi yang sudah dibatalkan tidak dapat diubah statusnya.");
        }

        // 2. Cegah PENDING -> PENDING (Redundan)
        if ($current === $target) {
            return;
        }

        // 3. Aturan Khusus Transition
        // PENDING -> PAID (OK)
        // PENDING -> CANCELLED (OK)
        // PAID -> CANCELLED (OK - Trigger Refund/Restock)
        // PAID -> PENDING (TIDAK BOLEH - Masa udah bayar jadi ngutang lagi?)

        if ($current === TransactionEnum::PAID && $target === TransactionEnum::PENDING) {
            throw new Exception("Transaksi yang sudah dibayar tidak dapat dikembalikan ke status Pending.");
        }
    }

    /**
     * [Compensating] Handle logic kompensasi (Reverse Transaction)
     * Mengembalikan stok & potensi refund
     */
    private function handleCancellationCompensation(Transaction $transaction): void
    {
        // 1. Kembalikan Stok (Compensating Stock)
        $this->restoreStock($transaction);

        // 2. [Future Improvement] Jika status sebelumnya PAID, trigger Refund
        if ($transaction->status === TransactionEnum::PAID) {
            // $this->paymentGateway->refund($transaction);
            // Log::info("Refund initiated for Transaction {$transaction->invoice_code}");
        }
    }

    /**
     * Restore stock when transaction cancelled
     */
    private function restoreStock(Transaction $transaction): void
    {
        $merchantId = $transaction->merchant_id;

        foreach ($transaction->transactionProducts as $item) {
            $productId = $item->product_id;
            $qty = $item->qty;

            // Get current stock
            DB::table('merchant_product')
                ->where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->increment('stock', $qty);

            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'merchant_id' => $merchantId,
                'type' => 'in',
                'amount' => $qty,
                'current_stock' => DB::table('merchant_product')
                    ->where('merchant_id', $merchantId)
                    ->where('product_id', $productId)
                    ->value('stock'),
                'reference_type' => Transaction::class,
                'reference_id' => $transaction->id,
                'note' => "Pembatalan Transaksi - Invoice: {$transaction->invoice_code}",
                'created_by' => Auth::id()
            ]);
        }
    }

    /**
     * Get transaction summary by merchant
     */
    public function getSummaryByMerchant(string $merchantId, array $dateRange = []): array
    {
        $filters = ['merchant_id' => $merchantId];

        if (!empty($dateRange['start_date'])) {
            $filters['start_date'] = $dateRange['start_date'];
        }

        if (!empty($dateRange['end_date'])) {
            $filters['end_date'] = $dateRange['end_date'];
        }

        $transactions = Transaction::where('merchant_id', $merchantId)
            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['end_date']))
            ->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->where('status', TransactionEnum::PAID)->sum('grand_total'),
            'pending_transactions' => $transactions->where('status', TransactionEnum::PENDING)->count(),
            'paid_transactions' => $transactions->where('status', TransactionEnum::PAID)->count(),
            'cancelled_transactions' => $transactions->where('status', TransactionEnum::CANCELLED)->count(),
        ];
    }
}
