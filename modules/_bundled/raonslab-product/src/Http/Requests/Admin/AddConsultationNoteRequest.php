<?php

namespace Modules\Raonslab\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddConsultationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
