<?php

namespace App\Http\Requests;

use App\Models\Installment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::in(array_keys(Installment::paymentMethodOptions()))],
            'status' => ['required', Rule::in(array_keys(Installment::statusOptions()))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
