<?php

declare(strict_types=1);

// Laravel을 부팅하지 않고 검증용 부트스트랩의 종료 시 바이트 복원을 검사한다.
require __DIR__.'/test-bootstrap.php';
if (file_put_contents(dirname(__DIR__, 3).'/.env', "# W03 own TEST-only overwrite fixture; no secrets\n") === false) {
    throw new RuntimeException('W03 env restoration fixture could not be written.');
}
echo "W03: own TEST-only env overwrite fixture created; shutdown must restore original private bytes.\n";
