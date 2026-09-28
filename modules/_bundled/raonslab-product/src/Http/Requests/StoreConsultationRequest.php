<?php

namespace Modules\Raonslab\Product\Http\Requests;

use App\Helpers\ResponseHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Modules\Raonslab\Product\Services\ConsultationConfigService;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! app(ConsultationConfigService::class)->isIntakeEnabled()) {
            throw new HttpResponseException(ResponseHelper::error('errors.503.message', 503));
        }

        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function rules(): array
    {
        $consentVersion = app(ConsultationConfigService::class)->consentVersion();

        return [
            'idempotency_key' => ['required', 'string', 'min:16', 'max:200', 'regex:/^[A-Za-z0-9._:\-]+$/'],
            'contact_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'message' => ['required', 'string', 'max:5000'],
            'privacy_consent' => ['required', 'accepted'],
            'privacy_consent_version' => ['required', 'string', 'max:100', Rule::in([$consentVersion])],
            'company' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'service_interest' => ['nullable', 'string', 'max:120'],
        ];
    }
}
