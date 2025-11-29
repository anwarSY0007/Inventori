<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMutationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Product info
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'slug' => $this->product->slug,
                    'name' => $this->product->name,
                    'thumbnail' => $this->product->thumbnail,
                    'category' => $this->when(
                        $this->product->relationLoaded('category'),
                        [
                            'id' => $this->product->category?->id,
                            'name' => $this->product->category?->name,
                        ]
                    ),
                ];
            }),

            // Location info
            'warehouse' => $this->whenLoaded('warehouse', function () {
                return $this->warehouse ? [
                    'id' => $this->warehouse->id,
                    'slug' => $this->warehouse->slug,
                    'name' => $this->warehouse->name,
                ] : null;
            }),

            'merchant' => $this->whenLoaded('merchant', function () {
                return $this->merchant ? [
                    'id' => $this->merchant->id,
                    'slug' => $this->merchant->slug,
                    'name' => $this->merchant->name,
                ] : null;
            }),

            // Mutation details
            'type' => $this->type,
            'type_label' => $this->type === 'in' ? 'Masuk' : 'Keluar',
            'amount' => $this->amount,
            'current_stock' => $this->current_stock,

            // Reference (polymorphic)
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference' => $this->whenLoaded('reference', function () {
                if (!$this->reference) return null;

                // Format berbeda tergantung tipe reference
                if ($this->reference instanceof \App\Models\Transaction) {
                    return [
                        'type' => 'transaction',
                        'invoice_code' => $this->reference->invoice_code,
                        'status' => $this->reference->status->value,
                    ];
                }

                return [
                    'type' => class_basename($this->reference),
                    'id' => $this->reference->id,
                ];
            }),

            'note' => $this->note,

            // Creator
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
