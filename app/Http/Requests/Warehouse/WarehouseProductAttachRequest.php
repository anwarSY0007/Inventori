<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseProductAttachRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|string|exists:products,id',
            'products.*.stock' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'Data produk wajib diisi',
            'products.array' => 'Format data produk tidak valid',
            'products.min' => 'Minimal 1 produk harus ditambahkan',
            'products.*.product_id.required' => 'ID produk wajib diisi',
            'products.*.product_id.exists' => 'Produk tidak ditemukan',
            'products.*.stock.required' => 'Stok produk wajib diisi',
            'products.*.stock.integer' => 'Stok harus berupa angka',
            'products.*.stock.min' => 'Stok minimal 0',
        ];
    }

    /**
     * Transform products array to pivot format
     * From: [{'product_id': 'uuid', 'stock': 100}]
     * To: {'uuid': {'stock': 100}}
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (isset($validated['products']) && is_array($validated['products'])) {
            $products = [];
            foreach ($validated['products'] as $product) {
                $products[$product['product_id']] = ['stock' => $product['stock']];
            }
            $validated['products'] = $products;
        }

        return $validated;
    }
}
