<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model untuk relasi Many-to-Many warehouse dan product
 * 
 * Model ini digunakan untuk akses langsung pivot table jika diperlukan
 * Untuk operasi normal, gunakan relationship dari Warehouse/Product model
 */
class WarehouseProduct extends Pivot
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'stock'
    ];

    protected $casts = [
        'stock' => 'integer',
    ];

    public $incrementing = false;

    /**
     * Relasi ke Warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relasi ke Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
