<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_code' => $this->invoice_code,
            'slug' => $this->slug,

            // Customer info
            'customer_name' => $this->name,
            'customer_phone' => $this->phone,

            // Financial
            'sub_total' => $this->sub_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'formatted_grand_total' => 'Rp ' . number_format($this->grand_total, 0, ',', '.'),

            // Status & Payment
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'payment_method' => $this->payment_method?->value,
            'payment_method_label' => $this->payment_method?->label(),
            'payment_reference' => $this->payment_reference,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),

            // Relations
            'merchant' => $this->whenLoaded('merchant', function () {
                return [
                    'id' => $this->merchant->id,
                    'slug' => $this->merchant->slug,
                    'name' => $this->merchant->name,
                ];
            }),

            'cashier' => $this->whenLoaded('cashier', function () {
                return [
                    'id' => $this->cashier?->id,
                    'name' => $this->cashier?->name,
                ];
            }),

            'products' => $this->whenLoaded('transactionProducts', function () {
                return $this->transactionProducts->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'product_thumbnail' => $item->product->thumbnail,
                        'category' => [
                            'id' => $item->product->category?->id,
                            'name' => $item->product->category?->name,
                        ],
                        'qty' => $item->qty,
                        'price' => $item->price,
                        'sub_total' => $item->sub_total,
                        'formatted_price' => 'Rp ' . number_format($item->price, 0, ',', '.'),
                        'formatted_sub_total' => 'Rp ' . number_format($item->sub_total, 0, ',', '.'),
                    ];
                });
            }),

            'total_items' => $this->whenLoaded('transactionProducts', function () {
                return $this->transactionProducts->sum('qty');
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
