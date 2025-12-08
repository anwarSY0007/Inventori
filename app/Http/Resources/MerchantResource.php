<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'phone' => $this->phone,
            'alamat' => $this->alamat,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Keeper information
            'keeper' => $this->whenLoaded('keeper', function () {
                return [
                    'id' => $this->keeper?->id,
                    'name' => $this->keeper?->name,
                    'email' => $this->keeper?->email,
                ];
            }),

            // Products with stock
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'slug' => $product->slug,
                        'name' => $product->name,
                        'thumbnail' => $product->thumbnail,
                        'price' => $product->price,
                        'stock' => $product->pivot->stock ?? 0,
                        'category' => $this->when(
                            $product->relationLoaded('category'),
                            [
                                'id' => $product->category?->id,
                                'slug' => $product->category?->slug,
                                'name' => $product->category?->name,
                            ]
                        ),
                    ];
                });
            }),

            // Total products count
            'total_products' => $this->whenLoaded('products', function () {
                return $this->products->count();
            }),
            'total_customers' => $this->total_customers ?? 0,
            // Total transactions count
            'total_transactions' => $this->transactions_sum_grand_total ?? 0,
        ];
    }
}
