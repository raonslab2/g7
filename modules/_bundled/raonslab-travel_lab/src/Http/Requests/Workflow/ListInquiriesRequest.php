<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Workflow;

class ListInquiriesRequest extends WorkflowRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
