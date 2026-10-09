<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Workflow;

use Illuminate\Validation\Rule;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;

class UpdateInquiryRequest extends WorkflowRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(InquiryStatus::class)],
            'admin_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
