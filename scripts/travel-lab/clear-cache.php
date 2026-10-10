<?php

declare(strict_types=1);
require __DIR__.'/environment.php';

try {
    travelLabClearGeneratedConfig();
    echo 'PASS: only marked checkout generated configuration cache cleared.'.PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().PHP_EOL);
    exit(1);
}
