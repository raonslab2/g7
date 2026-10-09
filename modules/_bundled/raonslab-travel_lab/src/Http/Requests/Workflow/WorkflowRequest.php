<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Workflow;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** 입력 검증은 FormRequest, 인증·권한은 미들웨어, 변경 가능 상태는 서비스에서 확인한다. */
class WorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allowed = array_filter(array_keys($this->rules()), fn ($key) => ! str_contains($key, '.'));
            foreach (array_diff(array_keys($this->all()), $allowed) as $key) {
                $validator->errors()->add($key, __('raonslab-travel_lab::workflow.unsupported_field'));
            }
        });
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(ResponseHelper::validationError($validator->errors()));
    }
}
