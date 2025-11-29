<?php

namespace App\Repositories;

use App\Enum\TransactionEnum;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    /**
     * Get all transactions with pagination
     */
    public function getAllTransaction(array $filters = [], array $field = ['*'], int $perPage = 25): LengthAwarePaginator
    {
        $query = Transaction::select($field)
            ->with(['merchant', 'cashier', 'transactionProducts.product.category'])
            ->latest();

        // Filter by merchant
        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Filter by payment method
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get transaction by ID
     */
    public function getTransactionById(string $id, array $field = ['*']): Transaction
    {
        return Transaction::select($field)
            ->with(['merchant', 'cashier', 'transactionProducts.product.category'])
            ->findOrFail($id);
    }

    /**
     * Get transaction by slug
     */
    public function getTransactionBySlug(string $slug, array $field = ['*']): Transaction
    {
        return Transaction::select($field)
            ->with(['merchant', 'cashier', 'transactionProducts.product.category'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Get transaction by invoice code
     */
    public function getTransactionByInvoice(string $invoiceCode, array $field = ['*']): Transaction
    {
        return Transaction::select($field)
            ->with(['merchant', 'cashier', 'transactionProducts.product.category'])
            ->where('invoice_code', $invoiceCode)
            ->firstOrFail();
    }

    /**
     * Create new transaction
     */
    public function createTransaction(array $data): Transaction
    {
        return Transaction::create($data);
    }

    /**
     * Update transaction
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh(['merchant', 'cashier', 'transactionProducts.product.category']);
    }

    /**
     * Delete transaction (soft delete)
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        return $transaction->delete();
    }

    /**
     * Attach products to transaction
     */
    public function attachProducts(Transaction $transaction, array $products): void
    {
        // Format: ['product_id' => ['qty' => 2, 'price' => 10000, 'sub_total' => 20000]]
        $transaction->products()->attach($products);
    }

    /**
     * Update transaction status
     */
    public function updateStatus(Transaction $transaction, TransactionEnum $status): Transaction
    {
        $transaction->update(['status' => $status]);

        if ($status === TransactionEnum::PAID && !$transaction->paid_at) {
            $transaction->update(['paid_at' => now()]);
        }

        return $transaction->fresh();
    }

    /**
     * Generate invoice code
     */
    public function generateInvoiceCode(string $merchantId): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$date}-{$random}";
    }
}
