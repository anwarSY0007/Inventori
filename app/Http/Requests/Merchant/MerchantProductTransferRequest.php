<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;


/**
 * Request untuk transfer product antar merchant
 */
class MerchantProductTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_merchant_id' => 'required|string|exists:merchants,id',
            'target_merchant_id' => 'required|string|exists:merchants,id|different:source_merchant_id',
            'product_id' => 'required|string|exists:products,id',
            'stock' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'source_merchant_id.required' => 'Merchant asal wajib dipilih',
            'source_merchant_id.exists' => 'Merchant asal tidak ditemukan',
            'target_merchant_id.required' => 'Merchant tujuan wajib dipilih',
            'target_merchant_id.exists' => 'Merchant tujuan tidak ditemukan',
            'target_merchant_id.different' => 'Merchant tujuan harus berbeda dengan merchant asal',
            'product_id.required' => 'Produk wajib dipilih',
            'product_id.exists' => 'Produk tidak ditemukan',
            'stock.required' => 'Jumlah stok yang ditransfer wajib diisi',
            'stock.integer' => 'Jumlah stok harus berupa angka',
            'stock.min' => 'Jumlah stok minimal 1',
        ];
    }
}
