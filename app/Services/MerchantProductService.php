<?php

namespace App\Services;

use App\Repositories\MerchantRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

            $existsInWarehouse = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->exists();

            if (!$existsInWarehouse) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di warehouse ini']
                ]);
            }
            $affected = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('stock', '>=', $amount)
                ->decrement('stock', $amount);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => ["Stok di warehouse tidak mencukupi atau data berubah saat transaksi."]
                ]);
            }

            $currentWarehouseStock = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->value('stock');

            $this->stockMutationService->recordStockOut([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'amount' => $amount,
                'current_stock' => $currentWarehouseStock,
                'note' => "Transfer ke Merchant (Assign)",
                'created_by' => $userId
            ]);

            $merchantId = $data['merchant_id'];

            $existsInMerchant = DB::table('merchant_product')
                ->where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->exists();

            if ($existsInMerchant) {
                DB::table('merchant_product')
                    ->where('merchant_id', $merchantId)
                    ->where('product_id', $productId)
                    ->increment('stock', $amount);
            } else {
                DB::table('merchant_product')->insert([
                    'merchant_id' => $merchantId,
                    'product_id'  => $productId,
                    'stock'       => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $currentMerchantStock = DB::table('merchant_product')
                ->where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->value('stock');

            $this->stockMutationService->recordStockIn([
                'product_id' => $productId,
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'current_stock' => $currentMerchantStock,
                'note' => "Terima dari Warehouse (Assign)",
                'created_by' => $userId
            ]);

            return [
                'status' => 'success',
                'moved_amount' => $amount,
                'warehouse_remaining' => $currentWarehouseStock,
                'merchant_stock' => $currentMerchantStock
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
