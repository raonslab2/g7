<?php

namespace Modules\Raonslab\TravelLab\Exceptions;

use RuntimeException;

/**
 * 고객지원 도메인 예외.
 *
 * 번역문과 함께 메시지 키·치환 파라미터·HTTP 상태를 보관해, 컨트롤러가
 * 원문이 아니라 키를 ResponseHelper 에 넘길 수 있게 한다.
 */
class TravelSupportException extends RuntimeException
{
    /**
     * @param  string  $messageKey  번역 키 (raonslab-travel_lab::support.*)
     * @param  int  $status  HTTP 상태 코드
     * @param  array<string, mixed>  $messageParams  치환 파라미터
     */
    public function __construct(
        private readonly string $messageKey,
        private readonly int $status = 400,
        private readonly array $messageParams = [],
    ) {
        parent::__construct(__($messageKey, $messageParams));
    }

    /**
     * 게시판이 아직 준비되지 않았습니다 (프로비저닝 미실행 또는 설정 불일치).
     */
    public static function notReady(): self
    {
        return new self('raonslab-travel_lab::support.errors.not_ready', 503);
    }

    /**
     * 문의를 찾을 수 없거나 열람 권한이 없습니다 (존재 여부를 노출하지 않음).
     */
    public static function questionNotFound(): self
    {
        return new self('raonslab-travel_lab::support.errors.question_not_found', 404);
    }

    /**
     * LAB 프로비저닝이 명시적으로 허용되지 않았습니다.
     */
    public static function provisioningNotAllowed(): self
    {
        return new self('raonslab-travel_lab::support.errors.provisioning_not_allowed', 409);
    }

    /**
     * 게시판이 존재하지만 고객지원 보안 기준과 다르게 설정되어 있습니다.
     */
    public static function boardMisconfigured(string $slug): self
    {
        return new self('raonslab-travel_lab::support.errors.board_misconfigured', 409, ['slug' => $slug]);
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

    public function getStatus(): int
    {
        return $this->status;
    }
}
