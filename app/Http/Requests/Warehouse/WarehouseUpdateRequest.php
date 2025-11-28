<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'alamat' => 'sometimes|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required_with:products|string|exists:products,id',
            'products.*.stock' => 'required_with:products|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Nama warehouse harus berupa teks',
            'phone.string' => 'Nomor telepon harus berupa teks',
            'thumbnail.image' => 'File harus berupa gambar',
            'thumbnail.max' => 'Ukuran gambar maksimal 2MB',
            'products.*.product_id.exists' => 'Produk tidak ditemukan',
            'products.*.stock.min' => 'Stok minimal 0',
        ];
    }

    /**
     * Transform products array to format: ['product_id' => ['stock' => qty]]
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
