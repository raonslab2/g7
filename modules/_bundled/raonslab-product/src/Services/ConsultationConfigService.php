<?php

namespace Modules\Raonslab\Product\Services;

class ConsultationConfigService
{
    /** @return array{intake_enabled: bool, consent_version: string, privacy_copy: string, privacy_policy_url: string, privacy_links: array<int, string>, privacy_contact: string, retention_notice: string} */
    public function publicConfig(): array
    {
        $privacyPolicyUrl = $this->value('privacy_policy_url');

        return [
            'intake_enabled' => $this->isIntakeEnabled(),
            'consent_version' => $this->value('consent_version'),
            'privacy_copy' => $this->value('privacy_copy'),
            'privacy_policy_url' => $privacyPolicyUrl,
            'privacy_links' => $privacyPolicyUrl === '' ? [] : [$privacyPolicyUrl],
            'privacy_contact' => $this->value('privacy_contact'),
            'retention_notice' => $this->value('retention_notice'),
        ];
    }

    public function isIntakeEnabled(): bool
    {
        if (config('raonslab-product-consultations.enabled') !== true) {
            return false;
        }

        foreach (['consent_version', 'privacy_copy', 'privacy_policy_url', 'privacy_contact', 'retention_notice'] as $key) {
            if ($this->value($key) === '') {
                return false;
            }
        }

        return filter_var($this->value('privacy_policy_url'), FILTER_VALIDATE_URL) !== false;
    }

    public function consentVersion(): string
    {
        return $this->value('consent_version');
    }

    public function notificationRecipient(): string
    {
        return $this->value('notification_to');
    }

    private function value(string $key): string
    {
        return trim((string) config("raonslab-product-consultations.{$key}", ''));
    }
}
