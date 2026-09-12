<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallmentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:60'],
            'first_due_date' => ['required', 'date'],
            'interval_months' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
