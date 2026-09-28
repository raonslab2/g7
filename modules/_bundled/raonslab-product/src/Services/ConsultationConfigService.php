<?php

namespace Modules\Raonslab\Product\Services;

use Illuminate\Http\Request;

class ConsultationConfigService
{
    public function __construct(private LegacyConsultationAudit $legacyAudit) {}

    /** @return array{intake_enabled: bool, consent_version: string, privacy_copy: string, privacy_policy_url: string, privacy_links: array<int, string>, privacy_contact: string, retention_notice: string} */
    public function publicConfig(Request $request): array
    {
        $privacyPolicyUrl = $this->value('privacy_policy_url');
        if (! $this->isTrustedHttpsUrl($privacyPolicyUrl)) {
            $privacyPolicyUrl = '';
        }

        return [
            'intake_enabled' => $this->isIntakeEnabled($request),
            'consent_version' => $this->value('consent_version'),
            'privacy_copy' => $this->value('privacy_copy'),
            'privacy_policy_url' => $privacyPolicyUrl,
            'privacy_links' => $privacyPolicyUrl === '' ? [] : [$privacyPolicyUrl],
            'privacy_contact' => $this->value('privacy_contact'),
            'retention_notice' => $this->value('retention_notice'),
        ];
    }

    public function isIntakeEnabled(Request $request): bool
    {
        if (config('raonslab-product-consultations.enabled') !== true || $this->legacyAudit->hasData()) {
            return false;
        }

        foreach (['consent_version', 'privacy_copy', 'privacy_policy_url', 'privacy_contact', 'retention_notice'] as $key) {
            if ($this->value($key) === '') {
                return false;
            }
        }

        return $this->isTrustedHttpsUrl((string) config('app.url'))
            && $this->isTrustedHttpsUrl($this->value('privacy_policy_url'))
            && $request->isSecure();
    }

    public function consentVersion(): string
    {
        return $this->value('consent_version');
    }

    public function isTrustedHttpsUrl(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $url) === 1) {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && ($parts['host'] ?? '') !== ''
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function value(string $key): string
    {
        return trim((string) config("raonslab-product-consultations.{$key}", ''));
    }
}
