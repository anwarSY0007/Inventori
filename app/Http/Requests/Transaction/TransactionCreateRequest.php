<?php

namespace App\Http\Requests\Transaction;

use App\Enum\PaymentEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request untuk create transaction (checkout)
 */
class TransactionCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => 'required|string|exists:merchants,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'tax_total' => 'nullable|integer|min:0',
            'payment_method' => ['nullable', Rule::enum(PaymentEnum::class)],
            'payment_reference' => 'nullable|string|max:255',

            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|string|exists:products,id',
            'products.*.qty' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_id.required' => 'Merchant wajib dipilih',
            'merchant_id.exists' => 'Merchant tidak ditemukan',
            'customer_name.required' => 'Nama customer wajib diisi',
            'customer_phone.required' => 'Nomor telepon customer wajib diisi',
            'tax_total.min' => 'Pajak tidak boleh negatif',
            'payment_method.enum' => 'Metode pembayaran tidak valid',

            'products.required' => 'Produk wajib diisi',
            'products.array' => 'Format produk tidak valid',
            'products.min' => 'Minimal 1 produk harus dipilih',
            'products.*.product_id.required' => 'ID produk wajib diisi',
            'products.*.product_id.exists' => 'Produk tidak ditemukan',
            'products.*.qty.required' => 'Jumlah produk wajib diisi',
            'products.*.qty.min' => 'Jumlah produk minimal 1',
        ];
    }
}
