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

    /** 공개 접수를 열기 전 운영자 승인이 필요한 설정 키 */
    public const APPROVAL_KEYS = ['consent_version', 'privacy_copy', 'privacy_policy_url', 'privacy_contact', 'retention_notice'];

    public function isIntakeEnabled(Request $request): bool
    {
        if (config('raonslab-product-consultations.enabled') !== true) {
            return false;
        }

        return ! in_array(false, $this->readinessChecks($request->isSecure()), true);
    }

    /**
     * 공개 접수 게이트의 조건별 충족 여부를 돌려줍니다. 값 자체는 싣지 않습니다.
     *
     * isIntakeEnabled()와 준비 상태 점검 명령이 같은 판정을 공유하는 단일 지점입니다.
     *
     * @param  bool|null  $requestSecure  HTTP 요청 문맥이 없으면 null (요청 HTTPS 조건 생략)
     * @return array<string, bool>
     */
    public function readinessChecks(?bool $requestSecure = null): array
    {
        $checks = [
            'enabled_flag' => config('raonslab-product-consultations.enabled') === true,
            'legacy_table_empty' => ! $this->legacyAudit->hasData(),
        ];

        foreach (self::APPROVAL_KEYS as $key) {
            $checks["{$key}_set"] = $this->value($key) !== '';
        }

        $checks['app_url_https'] = $this->isTrustedHttpsUrl((string) config('app.url'));
        $checks['privacy_policy_url_https'] = $this->isTrustedHttpsUrl($this->value('privacy_policy_url'));

        if ($requestSecure !== null) {
            $checks['request_https'] = $requestSecure;
        }

        return $checks;
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
