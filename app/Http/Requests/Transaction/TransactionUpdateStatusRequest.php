<?php

namespace App\Http\Requests\Transaction;

use App\Enum\TransactionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request untuk update status transaction
 */
class TransactionUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TransactionEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi',
            'status.enum' => 'Status tidak valid. Pilihan valid: pending, paid, failed, cancelled',
        ];
    }
}
