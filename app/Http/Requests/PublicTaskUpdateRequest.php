<?php

namespace App\Http\Requests;

use App\Models\ContractTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicTaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(ContractTask::statusOptions()))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
