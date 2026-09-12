<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicVendorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(Vendor::statusOptions()))],
            'payment_status' => ['required', Rule::in(array_keys(Vendor::paymentStatusOptions()))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
