<?php

namespace Modules\Raonslab\TravelLab\Exceptions;

use RuntimeException;
use Throwable;

/**
 * 캠페인 LAB 프로비저닝 도메인 예외 (키·치환 파라미터 보관).
 */
class TravelCampaignException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $messageParams
     */
    public function __construct(
        private readonly string $messageKey,
        private readonly array $messageParams = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(__($messageKey, $messageParams), 0, $previous);
    }

    public static function notAllowed(): self
    {
        return new self('raonslab-travel_lab::campaigns.errors.provisioning_not_allowed');
    }

    public static function unsafeEnvironment(string $setting, string $actual, string $expected): self
    {
        return new self('raonslab-travel_lab::campaigns.errors.unsafe_environment', [
            'setting' => $setting, 'actual' => $actual, 'expected' => $expected,
        ]);
    }

    public static function actorInvalid(): self
    {
        return new self('raonslab-travel_lab::campaigns.errors.actor_invalid');
    }

    public static function actorNotPermitted(int $actorId): self
    {
        return new self('raonslab-travel_lab::campaigns.errors.actor_not_permitted', ['actor' => $actorId]);
    }

    public static function slugConflict(string $slug, Throwable $previous): self
    {
        return new self('raonslab-travel_lab::campaigns.errors.slug_conflict', ['slug' => $slug], $previous);
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMessageParams(): array
    {
        return $this->messageParams;
    }
}
