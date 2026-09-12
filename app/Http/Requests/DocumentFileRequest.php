<?php

namespace App\Http\Requests;

use App\Models\DocumentFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'category' => ['required', Rule::in(array_keys(DocumentFile::categoryOptions()))],
            'client_id' => ['nullable', 'exists:clients,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'file' => ['required', 'file', 'max:20480'],
        ];
    }
}
