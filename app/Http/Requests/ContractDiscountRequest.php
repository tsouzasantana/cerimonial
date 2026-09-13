<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_type' => ['required', Rule::in(array_keys(Contract::discountTypeOptions()))],
            'discount_value_fixed' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'discount_value_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
