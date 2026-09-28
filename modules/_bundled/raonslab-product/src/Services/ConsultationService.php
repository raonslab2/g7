<?php

namespace Modules\Raonslab\Product\Services;

use Illuminate\Support\Arr;
use Modules\Raonslab\Product\Contracts\ConsultationBoardGateway;
use Modules\Raonslab\Product\Enums\ConsultationStatus;

class ConsultationService
{
    public function __construct(
        private ConsultationBoardGateway $boardGateway,
    ) {}

    public function submit(array $validated): ConsultationSubmissionResult
    {
        $payload = Arr::only($validated, [
            'contact_name', 'email', 'message', 'privacy_consent', 'privacy_consent_version',
            'company', 'phone', 'service_interest',
        ]);
        $payloadHash = hash_hmac('sha256', $this->canonicalJson($payload), (string) config('app.key'));
        $reference = 'RAON-'.strtoupper(substr(hash_hmac(
            'sha256',
            (string) $validated['idempotency_key'],
            (string) config('app.key'),
        ), 0, 26));

        return $this->boardGateway->persist(
            $reference,
            $payloadHash,
            [
                'schema' => 'raonslab-consultation-v1',
                'reference' => $reference,
                'payload_hash' => $payloadHash,
                'privacy_consent_version' => $validated['privacy_consent_version'],
                'privacy_consented_at' => now()->toIso8601String(),
                'contact' => [
                    'name' => $validated['contact_name'],
                    'email' => $validated['email'],
                    'company' => $validated['company'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ],
                'service_interest' => $validated['service_interest'] ?? null,
                'message' => $validated['message'],
            ],
            request()->ip(),
            ConsultationStatus::New,
        );
    }

    private function canonicalJson(array $payload): string
    {
        ksort($payload);

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
