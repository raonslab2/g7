<?php

declare(strict_types=1);

// 독립 TEST 전용 작업트리: APP 환경을 복제하거나 기본 G7 가드를 수정하지 않는다.
$w03Root = dirname(__DIR__, 3);
require_once $w03Root.'/scripts/travel-lab/environment.php';
$w03Environment = travelLabEnvironment(true);
$w03EnvPath = $w03Root.'/.env';
$w03Contents = file_get_contents($w03EnvPath);
if (is_link($w03EnvPath) || $w03Contents !== file_get_contents($w03Root.'/.env.testing')) {
    throw new RuntimeException('W03 bootstrap requires identical private TEST-only env files.');
}
// 동일 TEST 파일은 APP 설정이 아니므로 이름 비교 단계 동안만 존재를 숨긴다.
// Laravel 부팅 전에 원본 바이트/0600 모드를 복원한다. 부트스트랩 exit도 복원한다.
$w03Restore = static function () use ($w03EnvPath, $w03Contents): void {
    if (is_link($w03EnvPath)) {
        throw new RuntimeException('W03 private env became a symlink; refuse unsafe restoration.');
    }
    if (! is_file($w03EnvPath) || file_get_contents($w03EnvPath) !== $w03Contents) {
        if (file_put_contents($w03EnvPath, $w03Contents) === false) {
            throw new RuntimeException('W03 private env restoration failed.');
        }
    }
    if (! chmod($w03EnvPath, 0600)) {
        throw new RuntimeException('W03 private env mode restoration failed.');
    }
};
register_shutdown_function($w03Restore);
if (! unlink($w03EnvPath)) {
    throw new RuntimeException('W03 own TEST-only env could not be temporarily hidden.');
}
try {
    require $w03Root.'/tests/bootstrap.php';
} finally {
    $w03Restore();
}
// 파일 원본 출처 단언은 클래스 로딩만 수행하며 Laravel/DB를 부팅하지 않는다.
foreach ([
    Modules\Raonslab\TravelLab\Services\TravelSupportService::class,
    Modules\Raonslab\TravelLab\Services\TravelSupportProvisioner::class,
    Modules\Raonslab\TravelLab\Repositories\TravelSupportPostRepository::class,
    Modules\Sirsoft\Board\Services\PostService::class,
] as $w03Class) {
    $w03Source = (new ReflectionClass($w03Class))->getFileName();
    if (! str_starts_with($w03Source, $w03Root.'/modules/_bundled/')) {
        throw new RuntimeException('W03 source origin is outside own bundled checkout.');
    }
}
echo "W03: native bundled source origin verified; TEST-only env restored before Laravel bootstrap.\n";
