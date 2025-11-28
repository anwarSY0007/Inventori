<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;


/**
 * Pivot model untuk relasi Many-to-Many merchant dan product
 */
class MerchantProduct extends Pivot
{

    protected $fillable = ['merchant_id', 'product_id', 'stock'];

    protected $casts = [
        'stock' => 'integer',
    ];

    public $incrementing = false;

    /**
     * Relasi ke Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke Merchant
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
