<?php

namespace Modules\Raonslab\Product\Exceptions;

use RuntimeException;

/** 안내 Page 교체의 사전 조건이 하나라도 맞지 않아 아무것도 쓰지 않고 중단했음을 알린다. */
class InfoPageRemediationConflict extends RuntimeException
{
    /**
     * @param  array<string, string>  $conflicts  slug => 사유
     */
    public function __construct(public readonly array $conflicts)
    {
        parent::__construct('Info Page remediation aborted without writes: '.implode(', ', array_map(
            fn (string $slug, string $reason): string => "{$slug}={$reason}",
            array_keys($conflicts),
            $conflicts,
        )));
    }
}
