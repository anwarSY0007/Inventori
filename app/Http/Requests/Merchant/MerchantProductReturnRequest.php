<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;


/**
 * Request untuk return product dari merchant ke warehouse
 */
class MerchantProductReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|string|exists:warehouses,id',
            'merchant_id' => 'required|string|exists:merchants,id',
            'product_id' => 'required|string|exists:products,id',
            'stock' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Warehouse tujuan wajib dipilih',
            'warehouse_id.exists' => 'Warehouse tidak ditemukan',
            'merchant_id.required' => 'Merchant wajib dipilih',
            'merchant_id.exists' => 'Merchant tidak ditemukan',
            'product_id.required' => 'Produk wajib dipilih',
            'product_id.exists' => 'Produk tidak ditemukan',
            'stock.required' => 'Jumlah stok yang dikembalikan wajib diisi',
            'stock.integer' => 'Jumlah stok harus berupa angka',
            'stock.min' => 'Jumlah stok minimal 1',
        ];
    }
}
