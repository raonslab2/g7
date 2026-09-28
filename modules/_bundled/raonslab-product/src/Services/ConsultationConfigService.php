<?php

namespace Modules\Raonslab\Product\Services;

use Illuminate\Http\Request;

/**
 * 상담 접수 공개 설정과 접수 가능 여부의 단일 판정 지점입니다.
 *
 * 접수는 운영자가 필요한 값을 모두 명시하고, 공개 사이트 주소와 게시되는 모든 주소가
 * 신뢰 가능한 https 일 때만 열립니다(fail-closed). TLS 여부는 설정값과 Laravel 의
 * 신뢰 프록시 판정(`Request::isSecure()`)으로만 봅니다 — 신뢰하지 않은 X-Forwarded-* 는
 * 근거로 삼지 않습니다.
 */
class ConsultationConfigService
{
    private const REQUIRED_KEYS = ['consent_version', 'privacy_copy', 'privacy_policy_url', 'privacy_contact', 'retention_notice'];

    /** @return array{intake_enabled: bool, consent_version: string, privacy_copy: string, privacy_policy_url: string, privacy_links: array<int, string>, privacy_contact: string, retention_notice: string} */
    public function publicConfig(Request $request): array
    {
        $privacyPolicyUrl = $this->value('privacy_policy_url');
        if (! $this->isTrustedHttpsUrl($privacyPolicyUrl)) {
            $privacyPolicyUrl = '';
        }

        $privacyContact = $this->value('privacy_contact');
        if (! $this->isSafeContact($privacyContact)) {
            $privacyContact = '';
        }

        return [
            'intake_enabled' => $this->isIntakeEnabled($request),
            'consent_version' => $this->value('consent_version'),
            'privacy_copy' => $this->value('privacy_copy'),
            'privacy_policy_url' => $privacyPolicyUrl,
            'privacy_links' => $privacyPolicyUrl === '' ? [] : [$privacyPolicyUrl],
            'privacy_contact' => $privacyContact,
            'retention_notice' => $this->value('retention_notice'),
        ];
    }

    public function isIntakeEnabled(Request $request): bool
    {
        if (config('raonslab-product-consultations.enabled') !== true) {
            return false;
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if ($this->value($key) === '') {
                return false;
            }
        }

        return $this->isTrustedHttpsUrl(trim((string) config('app.url', '')))
            && $this->isTrustedHttpsUrl($this->value('privacy_policy_url'))
            && $this->isSafeContact($this->value('privacy_contact'))
            && $request->isSecure();
    }

    public function consentVersion(): string
    {
        return $this->value('consent_version');
    }

    public function notificationRecipient(): string
    {
        return $this->value('notification_to');
    }

    /**
     * 절대 https 주소만 허용합니다. 상대 경로·다른 스킴(javascript/data/http 등)·
     * 사용자 정보(`https://a@b`)·공백/제어문자/역슬래시가 섞인 값은 거부합니다.
     */
    public function isTrustedHttpsUrl(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $url) === 1) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        if (($parts['host'] ?? '') === '' || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 개인정보 담당 연락처는 이메일·전화 같은 문구일 수 있습니다. 다만 문구 안에 주소가
     * 들어 있으면 그 주소는 모두 신뢰 가능한 https 여야 하고, 스크립트성 스킴은 거부합니다.
     */
    public function isSafeContact(string $contact): bool
    {
        if ($contact === '' || preg_match('/(?:javascript|vbscript|data|file)\s*:/i', $contact) === 1) {
            return false;
        }

        preg_match_all('#[a-z][a-z0-9+.\-]*://[^\s<>"\']+#i', $contact, $matches);
        foreach ($matches[0] as $url) {
            if (! $this->isTrustedHttpsUrl(rtrim($url, '.,;:)'))) {
                return false;
            }
        }

        return true;
    }

    private function value(string $key): string
    {
        return trim((string) config("raonslab-product-consultations.{$key}", ''));
    }
}
