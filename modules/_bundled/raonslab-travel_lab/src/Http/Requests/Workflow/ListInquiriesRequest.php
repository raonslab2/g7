<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Workflow;

use Illuminate\Validation\Rule;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;

class ListInquiriesRequest extends WorkflowRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(InquiryStatus::class)],
        ];
    }
}
