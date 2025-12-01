<?php

namespace App\Services;

use App\Models\StockMutation;
use App\Repositories\StockMutationRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockMutationService
{
    public function __construct(
        protected StockMutationRepository $stockMutationRepository
    ) {}

    /**
     * Get all stock mutations
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->stockMutationRepository->getAllMutation($filters);
    }

    /**
     * Get mutation by ID
     */
    public function getById(string $id): StockMutation
    {
        return $this->stockMutationRepository->getMutationById($id);
    }

    /**
     * Get mutations by product
     */
    public function getByProduct(string $productId, int $limit = 10): Collection
    {
        return $this->stockMutationRepository->getByProduct($productId, $limit);
    }

    /**
     * Get mutations by reference (e.g., all mutations for a specific transaction)
     */
    public function getByReference(string $referenceType, string $referenceId): Collection
    {
        return $this->stockMutationRepository->getByReference($referenceType, $referenceId);
    }

    /**
     * Record stock mutation
     * This is the main method called by other services
     */
    public function recordMutation(array $data): StockMutation
    {
        return DB::transaction(function () use ($data) {
            if (!in_array($data['type'] ?? '', ['in', 'out'])) {
                throw new Exception("Tipe mutasi tidak valid (harus 'in' atau 'out').");
            }
            $mutationData = array_merge([
                'warehouse_id'   => null,
                'merchant_id'    => null,
                'reference_type' => null,
                'reference_id'   => null,
                'note'           => null,
                'created_by'     => Auth::id(),
            ], $data);

            $mutation = $this->stockMutationRepository->createMutation($mutationData);

            Log::info("Stock Mutation Recorded: {$mutation->type} - Product ID: {$mutation->product_id}", [
                'amount' => $mutation->amount,
                'current_stock' => $mutation->current_stock,
                'reference' => $mutation->reference_type . ':' . $mutation->reference_id,
                'user_id' => $mutation->created_by,
            ]);

            return $mutation;
        });
    }

    /**
     * Record stock IN (masuk)
     * Digunakan saat: restocking, transfer masuk, pembatalan transaksi
     */
    public function recordStockIn(array $data): StockMutation
    {
        $data['type'] = 'in';
        return $this->recordMutation($data);
    }

    /**
     * Record stock OUT (keluar)
     * Digunakan saat: penjualan, transfer keluar, kerusakan
     */
    public function recordStockOut(array $data): StockMutation
    {
        if (empty($data['product_id']) || empty($data['amount'])) {
            throw new Exception("Product ID dan Amount wajib diisi untuk mutasi keluar.");
        }

        $warehouseId = $data['warehouse_id'] ?? null;
        $merchantId  = $data['merchant_id'] ?? null;

        if (!isset($data['current_stock'])) {
            $isSufficient = $this->validateStock(
                $data['product_id'],
                $data['amount'],
                $warehouseId,
                $merchantId
            );

            if (!$isSufficient) {
                $current = $this->getCurrentStock($data['product_id'], $warehouseId, $merchantId);
                throw new Exception("Stok tidak mencukupi untuk mutasi keluar. Tersedia: {$current}, Diminta: {$data['amount']}");
            }
        }

        $data['type'] = 'out';
        return $this->recordMutation($data);
    }

    /**
     * Get stock movement history for a product
     */
    public function getProductHistory(string $productId, array $filters = []): array
    {
        $mutations = $this->stockMutationRepository->getAllMutation(
            array_merge($filters, ['product_id' => $productId])
        );

        $summary = $this->stockMutationRepository->getStockSummary($productId, $filters);

        return [
            'mutations' => $mutations,
            'summary'   => $summary,
        ];
    }

    /**
     * Get stock summary by product
     */
    public function getStockSummary(string $productId, array $filters = []): array
    {
        return $this->stockMutationRepository->getStockSummary($productId, $filters);
    }

    /**
     * Get warehouse stock report
     */
    public function getWarehouseReport(string $warehouseId, array $filters = []): array
    {
        return $this->generateStockReport($filters, 'warehouse_id', $warehouseId);
    }

    /**
     * Get merchant stock report
     */
    public function getMerchantReport(string $merchantId, array $filters = []): array
    {
        return $this->generateStockReport($filters, 'merchant_id', $merchantId);
    }

    /**
     * Reusable logic for generating report
     */
    private function generateStockReport(array $filters, string $scopeKey, string $scopeId): array
    {
        $filters[$scopeKey] = $scopeId;

        $mutations = $this->stockMutationRepository->getAllMutation($filters);

        $items = $mutations instanceof LengthAwarePaginator
            ? collect($mutations->items())
            : $mutations;

        $productSummary = $items->groupBy('product_id')
            ->map(fn($group) => $this->calculateProductStats($group))
            ->values();

        return [
            $scopeKey         => $scopeId,
            'products'        => $productSummary,
            'total_products'  => $productSummary->count(),
            'total_mutations' => $mutations instanceof LengthAwarePaginator ? $mutations->total() : $mutations->count(),
        ];
    }

    /**
     * Extracted method untuk perhitungan statistik produk
     * Mengurangi kompleksitas cognitive (clean code)
     */
    private function calculateProductStats(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [
                'product_id'    => null,
                'product_name'  => 'Unknown Product',
                'total_in'      => 0,
                'total_out'     => 0,
                'net_change'    => 0,
                'current_stock' => 0,
            ];
        }

        $product = $items->first()->product;
        $totalIn  = $items->where('type', 'in')->sum('amount');
        $totalOut = $items->where('type', 'out')->sum('amount');
        $lastMutation = $items->sortByDesc('created_at')->first();

        return [
            'product_id'    => $product->id ?? null,
            'product_name'  => $product->name ?? 'Unknown Product',
            'total_in'      => $totalIn,
            'total_out'     => $totalOut,
            'net_change'    => $totalIn - $totalOut,
            'current_stock' => $lastMutation->current_stock ?? 0,
        ];
    }

    /**
     * Get current stock from latest mutation
     */
    public function getCurrentStock(string $productId, ?string $warehouseId = null, ?string $merchantId = null): int
    {
        $latestMutation = $this->stockMutationRepository->getLatestMutation(
            $productId,
            $warehouseId,
            $merchantId
        );

        return $latestMutation?->current_stock ?? 0;
    }

    /**
     * Validate stock before operation
     */
    public function validateStock(string $productId, int $requiredAmount, ?string $warehouseId = null, ?string $merchantId = null): bool
    {
        return $this->getCurrentStock($productId, $warehouseId, $merchantId) >= $requiredAmount;
    }
}
