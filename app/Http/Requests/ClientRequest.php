<?php

namespace App\Http\Requests;

use App\Rules\BrazilianPhoneNumber;
use App\Rules\CpfOrCnpj;
use App\Support\BrazilianStates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'phone' => ['nullable', 'string', 'max:20', new BrazilianPhoneNumber],
            'phone_alt' => ['nullable', 'string', 'max:20', new BrazilianPhoneNumber],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'address_district' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', Rule::in(array_keys(BrazilianStates::options()))],
            'address_zipcode' => ['nullable', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'birth_date' => ['nullable', 'date', 'after:1900-01-01', 'before:today'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'address_zipcode.regex' => 'Informe um CEP válido (00000-000).',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'birth_date.after' => 'Informe uma data de nascimento válida.',
            'address_state.in' => 'Selecione um estado válido.',
        ];
    }
}
