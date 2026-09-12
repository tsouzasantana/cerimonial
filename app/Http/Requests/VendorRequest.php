<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'vendor_service_type_id' => ['required_without:new_service_type', 'nullable', 'exists:vendor_service_types,id'],
            'new_service_type' => ['required_without:vendor_service_type_id', 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Vendor::statusOptions()))],
            'payment_status' => ['required', Rule::in(array_keys(Vendor::paymentStatusOptions()))],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_service_type_id.required_without' => 'Selecione um tipo de serviço ou digite um novo.',
            'new_service_type.required_without' => 'Selecione um tipo de serviço ou digite um novo.',
        ];
    }
}
