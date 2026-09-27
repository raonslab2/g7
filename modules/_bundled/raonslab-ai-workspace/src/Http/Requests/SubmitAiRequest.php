<?php

namespace Modules\Raonslab\Ai\Workspace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(['CODEX', 'CLAUDE'])],
            'profile' => ['required', 'string', 'max:80'],
            'prompt' => ['required', 'string', 'max:1000000'],
            'attachment_ids' => ['sometimes', 'array', 'max:32'],
            'attachment_ids.*' => ['string', 'max:160'],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }
}
