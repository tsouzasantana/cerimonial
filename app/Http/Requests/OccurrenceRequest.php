<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'occurrence_type_id' => ['required', 'exists:occurrence_types,id'],
            'occurrence_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ];
    }
}
