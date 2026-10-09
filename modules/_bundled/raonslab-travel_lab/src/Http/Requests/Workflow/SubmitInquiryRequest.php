<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Workflow;

use Illuminate\Contracts\Validation\Validator;

class SubmitInquiryRequest extends WorkflowRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->exists('idempotency_key') && $this->hasHeader('Idempotency-Key')) {
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }

    public function rules(): array
    {
        return [
            'cart_ids' => ['required', 'array', 'min:1', 'max:100'],
            'cart_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'contact' => ['required', 'array:name,phone'],
            'contact.name' => ['required', 'string', 'max:100'],
            'contact.phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:\-]*$/D'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator) {
            if ($this->hasHeader('Idempotency-Key') && $this->input('idempotency_key') !== $this->header('Idempotency-Key')) {
                $validator->errors()->add('idempotency_key', __('raonslab-travel_lab::workflow.idempotency_mismatch'));
            }
        });
    }
}
