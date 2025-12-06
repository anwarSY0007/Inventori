<?php

namespace App\Models;

use App\Enum\PaymentEnum;
use App\Enum\TransactionEnum;
use App\Traits\HasSlug;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, UUID, HasSlug;

    protected $fillable = [
        'invoice_code',
        'slug',
        'name',
        'phone',
        'sub_total',
        'tax_total',
        'grand_total',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'merchant_id',
        'cashier_id',
    ];

    /**
     * Casting tipe data otomatis
     */
    protected $casts = [
        'paid_at' => 'datetime',
        'status' => TransactionEnum::class,
        'payment_method' => PaymentEnum::class,
    ];

    /**
     * Relasi ke Merchant (Pemilik Transaksi)
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Relasi ke User (Kasir yang melayani)
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    /**
     * Relasi ke transaction Produk yang dibeli
     * Mengambil data dari tabel pivot 'transaction_product'
     */
    public function transactionProducts(): HasMany
    {
        return $this->hasMany(TransactionProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'transaction_product')
            ->using(TransactionProduct::class)
            ->withPivot(['price', 'sub_total', 'qty'])
            ->withTimestamps();
    }
}
