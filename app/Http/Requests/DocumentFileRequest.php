<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'file' => ['required_without:url', 'nullable', 'file', 'max:20480'],
            'url' => ['required_without:file', 'nullable', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_without' => 'Envie um arquivo ou informe um link de documento na nuvem.',
            'url.required_without' => 'Envie um arquivo ou informe um link de documento na nuvem.',
        ];
    }
}
