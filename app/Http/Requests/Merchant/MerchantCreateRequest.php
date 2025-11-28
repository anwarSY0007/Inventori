<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class MerchantCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'alamat' => 'required|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'keeper_id' => 'required|string|exists:users,id',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required_with:products|string|exists:products,id',
            'products.*.stock' => 'required_with:products|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama toko wajib diisi',
            'phone.required' => 'Nomor telepon wajib diisi',
            'alamat.required' => 'Alamat wajib diisi',
            'keeper_id.required' => 'Pemilik toko wajib dipilih',
            'keeper_id.exists' => 'Pemilik toko tidak ditemukan',
            'thumbnail.image' => 'File harus berupa gambar',
            'thumbnail.max' => 'Ukuran gambar maksimal 2MB',
            'products.*.product_id.exists' => 'Produk tidak ditemukan',
            'products.*.stock.min' => 'Stok minimal 0',
        ];
    }

    /**
     * Transform products array to pivot format
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
