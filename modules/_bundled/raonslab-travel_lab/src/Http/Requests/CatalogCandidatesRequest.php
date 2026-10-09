<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatalogCandidatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
