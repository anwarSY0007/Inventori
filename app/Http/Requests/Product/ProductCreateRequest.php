<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|string|exists:categories,id',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_popular' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Nama produk harus berupa teks',
            'name.max' => 'Nama produk maksimal 255 karakter',
            'price.integer' => 'Harga harus berupa angka',
            'price.min' => 'Harga tidak boleh kurang dari 0',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan',
            'thumbnail.image' => 'File harus berupa gambar',
            'thumbnail.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp',
            'thumbnail.max' => 'Ukuran gambar maksimal 2MB',
            'is_popular.boolean' => 'Status populer harus berupa true/false',
        ];
    }
}
