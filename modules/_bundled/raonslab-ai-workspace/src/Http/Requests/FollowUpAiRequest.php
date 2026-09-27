<?php

namespace Modules\Raonslab\Ai\Workspace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FollowUpAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required_without:answers', 'nullable', 'string', 'max:1000000'],
            'answers' => ['required_without:text', 'nullable', 'array', 'max:32'],
            'answers.*' => ['required', 'string', 'max:100000'],
            'attachment_ids' => ['sometimes', 'array', 'max:32'],
            'attachment_ids.*' => ['string', 'max:160'],
            'idempotency_key' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
