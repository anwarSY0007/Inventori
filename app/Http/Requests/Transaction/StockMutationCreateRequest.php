<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request untuk create manual stock mutation (adjustment)
 */
class StockMutationCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|string|exists:products,id',
            'warehouse_id' => 'nullable|string|exists:warehouses,id',
            'merchant_id' => 'nullable|string|exists:merchants,id',
            'type' => 'required|in:in,out',
            'amount' => 'required|integer|min:1',
            'current_stock' => 'required|integer|min:0',
            'note' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih',
            'product_id.exists' => 'Produk tidak ditemukan',
            'warehouse_id.exists' => 'Warehouse tidak ditemukan',
            'merchant_id.exists' => 'Merchant tidak ditemukan',
            'type.required' => 'Tipe mutasi wajib dipilih',
            'type.in' => 'Tipe mutasi harus "in" atau "out"',
            'amount.required' => 'Jumlah wajib diisi',
            'amount.min' => 'Jumlah minimal 1',
            'current_stock.required' => 'Stok saat ini wajib diisi',
            'current_stock.min' => 'Stok tidak boleh negatif',
            'note.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}
