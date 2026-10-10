<?php

namespace Modules\Raonslab\TravelLab\Exceptions;

use RuntimeException;

/** 잠금 안에서 확인하는 재고·일정 불변조건 충돌입니다. */
class CatalogConflictException extends RuntimeException
{
    public function __construct(private readonly string $messageKey)
    {
        parent::__construct(__('raonslab-travel_lab::'.$messageKey));
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }
}
