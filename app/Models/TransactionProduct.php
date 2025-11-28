<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionProduct extends Pivot
{
   protected $fillable = ['transaction_id', 'product_id', 'price', 'sub_total', 'qty'];


   public function transaction(): BelongsTo
   {
      return $this->belongsTo(Transaction::class);
   }

   public function product(): BelongsTo
   {
      return $this->belongsTo(Product::class);
   }
}
