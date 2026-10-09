<?php

declare(strict_types=1);

// APP용 원본 캡처의 cleanup 코드를 TEST 스키마에서만 독립 실행한다.
$w03Root = dirname(__DIR__, 3);
require __DIR__.'/test-bootstrap.php';
function w03TestCaptureApp(): Illuminate\Foundation\Application
{
    $app = travelLabApp(true);
    App\Extension\Testing\ExtensionTestAllowlist::set([
        'modules/sirsoft-board', 'modules/sirsoft-page',
        'modules/sirsoft-ecommerce', 'modules/raonslab-travel_lab',
    ]);
    foreach ([
        Modules\Sirsoft\Board\Providers\BoardServiceProvider::class,
        Modules\Sirsoft\Page\Providers\PageServiceProvider::class,
        Modules\Sirsoft\Ecommerce\Providers\EcommerceServiceProvider::class,
        Modules\Raonslab\TravelLab\Providers\TravelLabServiceProvider::class,
    ] as $provider) {
        $app->register($provider);
    }
    Illuminate\Support\Facades\Route::prefix('api/modules/raonslab-travel_lab')
        ->name('api.modules.raonslab-travel_lab.')->middleware('api')
        ->group(base_path('modules/_bundled/raonslab-travel_lab/src/routes/api.php'));

    return $app;
}
$source = file_get_contents($w03Root.'/scripts/travel-lab/live-api-responses.php');
if (hash('sha256', $source) !== '808b987e43608cef99c5e8f68832a7528cb9e272c07ca9cefa59a946ba9c1124') {
    throw new RuntimeException('W03 API capture source hash mismatch.');
}
// 실제 APP 캡처를 실행하지 않는다. 명시된 2개 치환만 수행하며 cleanup 본문은 동일하다.
$source = str_replace('__DIR__', var_export($w03Root.'/scripts/travel-lab', true), $source);
$source = str_replace('$app = travelLabApp();', '$app = w03TestCaptureApp();', $source, $replacements);
if ($replacements !== 1) {
    throw new RuntimeException('W03 TEST-only capture adaptation failed.');
}
$argv = [__FILE__, '--fail-after-inquiry'];
eval(substr($source, 5)); // 원본 exit(1)은 의도한 실패 주입이며 별도 프로세스에서 지속 상태를 검증한다.
