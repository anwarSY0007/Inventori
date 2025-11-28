<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMutation extends Model
{

    use HasFactory, SoftDeletes, UUID;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'merchant_id',
        'type',     
        'amount',
        'current_stock', 
        'reference_type',
        'reference_id',
        'note',
        'created_by',
    ];

    /**
     * Relasi ke Produk
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke Gudang (Bisa null jika mutasi terjadi di merchant)
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relasi ke Merchant (Bisa null jika mutasi terjadi di gudang pusat)
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Relasi ke User pembuat mutasi (Staff/Admin)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * RELASI SAKTI (Polymorphic)
     * Ini otomatis mendeteksi apakah mutasi ini karena 'Transaction', 'Purchase', atau 'Adjustment'
     * tanpa perlu kita coding if-else.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
