<?php

namespace Modules\Raonslab\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Raonslab\Product\Enums\ConsultationStatus;

class UpdateConsultationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ConsultationStatus::class)],
            'close_outcome' => [
                'nullable',
                'string',
                'max:160',
                'required_if:status,'.ConsultationStatus::Closed->value,
                'prohibited_unless:status,'.ConsultationStatus::Closed->value,
            ],
        ];
    }
}
