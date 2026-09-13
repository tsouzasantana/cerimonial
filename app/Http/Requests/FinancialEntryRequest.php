<?php

namespace App\Http\Requests;

use App\Models\Installment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::in(array_keys(Installment::paymentMethodOptions()))],
            'status' => ['required', Rule::in(array_keys(Installment::statusOptions()))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
