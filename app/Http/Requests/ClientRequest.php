<?php

namespace App\Http\Requests;

use App\Rules\CpfOrCnpj;
use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20', new CpfOrCnpj],
            'rg' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_alt' => ['nullable', 'string', 'max:20'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'address_district' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'max:2'],
            'address_zipcode' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
