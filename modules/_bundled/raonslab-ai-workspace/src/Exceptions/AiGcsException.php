<?php

namespace Modules\Raonslab\Ai\Workspace\Exceptions;

use RuntimeException;

class AiGcsException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 502,
        public readonly string $errorCode = 'AIGCS_REQUEST_FAILED',
    ) {
        parent::__construct($message);
    }
}
