<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'event_date' => ['required', 'date'],
            'event_location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Contract::statusOptions()))],
            'signed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
