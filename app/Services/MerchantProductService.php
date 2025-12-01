<?php

namespace App\Services;

use App\Repositories\MerchantRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MerchantProductService
{
    public function __construct(
        protected MerchantRepository $merchantRepository,
        protected StockMutationService $stockMutationService
    ) {}

    /**
     * Assign product dari warehouse ke merchant
     * Stock di warehouse akan berkurang, stock di merchant bertambah
     */
    public function assignProductToMerchant(array $data)
    {
        return DB::transaction(function () use ($data) {
            $warehouseId = $data['warehouse_id'];
            $productId   = $data['product_id'];
            $amount      = $data['stock'];
            $userId      = Auth::id();

            // 1. Validasi Eksistensi di Gudang
            $existsInWarehouse = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->exists();

            if (!$existsInWarehouse) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di warehouse ini']
                ]);
            }

            // 2. Kurangi Stok Gudang (Atomic Decrement)
            // Mengembalikan jumlah baris yang ter-update (0 jika stok tidak cukup)
            $affected = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('stock', '>=', $amount) // [Validation] Pastikan stok cukup di query level
                ->decrement('stock', $amount);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => ['Stok di warehouse tidak mencukupi untuk transfer ini.']
                ]);
            }

            // 3. Tambah Stok Merchant (Update or Create)
            $merchant = $this->merchantRepository->getMerchantById($data['merchant_id'], ['id', 'name']);

            // Cek apakah merchant sudah punya produk ini
            $existsInMerchant = DB::table('merchant_product')
                ->where('merchant_id', $merchant->id)
                ->where('product_id', $productId)
                ->exists();

            if ($existsInMerchant) {
                DB::table('merchant_product')
                    ->where('merchant_id', $merchant->id)
                    ->where('product_id', $productId)
                    ->increment('stock', $amount);
            } else {
                $this->merchantRepository->attachProducts($merchant, [
                    $productId => ['stock' => $amount]
                ]);
            }

            // 4. Catat Mutasi Keluar (Gudang) & Masuk (Merchant)
            // Logika mutasi di sini disederhanakan untuk pencatatan history
            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'out',
                'amount' => $amount,
                'note' => "Transfer ke Merchant: {$merchant->name}",
                'created_by' => $userId
            ]);

            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'merchant_id' => $merchant->id,
                'type' => 'in',
                'amount' => $amount,
                'note' => "Transfer masuk dari Warehouse",
                'created_by' => $userId
            ]);

            // [Audit Log]
            Log::info("Stock Transfer: Warehouse -> Merchant", [
                'warehouse_id' => $warehouseId,
                'merchant_id' => $merchant->id,
                'product_id' => $productId,
                'amount' => $amount,
                'user_id' => $userId
            ]);

            return [
                'status' => 'success',
                'moved_amount' => $amount,
            ];
        });
    }

    /**
     * Get stock movement history between warehouse and merchant
     */
    public function getStockMovementHistory(string $merchantId, string $productId): array
    {
        $merchant = $this->merchantRepository->getMerchantById($merchantId, ['id', 'name']);

        $stock = DB::table('merchant_product')
            ->where('merchant_id', $merchantId)
            ->where('product_id', $productId)
            ->value('stock');

        $history = $this->stockMutationService->getProductHistory($productId, [
            'merchant_id' => $merchantId
        ]);

        if (is_null($stock)) {
            throw ValidationException::withMessages([
                'product_id' => ['Produk tidak ditemukan di merchant ini']
            ]);
        }

        $history = $this->stockMutationService->getProductHistory($productId, [
            'merchant_id' => $merchantId
        ]);

        return [
            'merchant' => [
                'id'   => $merchant->id,
                'name' => $merchant->name,
            ],
            'product' => [
                'id'            => $productId,
                'current_stock' => $stock,
            ],
            'movements' => $history // Todo: Ambil dari tabel mutasi jika ada
        ];
    }
}
