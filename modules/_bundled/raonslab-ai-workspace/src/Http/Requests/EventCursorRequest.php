<?php

namespace Modules\Raonslab\Ai\Workspace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventCursorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'after' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
