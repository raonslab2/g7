<?php
// 공개 접수를 열지 않는 내부 비식별 운영 검증입니다. 부팅별 멱등키로 새 lead 한 건만 보존합니다.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $bootId = trim(file_get_contents('/proc/sys/kernel/random/boot_id'));
    if (! preg_match('/^[a-f0-9-]{36}$/', $bootId)) {
        throw new RuntimeException('Invalid boot ID');
    }
    $admin = App\Models\User::where('is_super', true)->firstOrFail();
    Illuminate\Support\Facades\Auth::setUser($admin);
    $payload = [
        'idempotency_key' => 'aws-boot-internal-'.$bootId,
        'contact_name' => 'AWS internal operational verification',
        'email' => 'aws-boot-verification@example.invalid',
        'message' => 'Synthetic internal operational verification; not a customer submission. Boot '.$bootId,
        'privacy_consent' => true,
        'privacy_consent_version' => 'synthetic-internal-operational-verification',
        'company' => null,
        'phone' => null,
        'service_interest' => 'pilot',
    ];
    // 공식 FormRequest의 입력 규칙만 재사용합니다. 공개 HTTP prepareForValidation/접수 게이트는 호출하지 않습니다.
    // 동의 버전은 이 CLI 프로세스의 합성 데이터 검증에만 쓰며 운영 .env/캐시/접수 허용 플래그는 바꾸지 않습니다.
    $previous = config('raonslab-product-consultations.consent_version');
    try {
        config(['raonslab-product-consultations.consent_version' => $payload['privacy_consent_version']]);
        $rules = (new Modules\Raonslab\Product\Http\Requests\StoreConsultationRequest)->rules();
        $validated = Illuminate\Support\Facades\Validator::make($payload, $rules)->validate();
    } finally {
        config(['raonslab-product-consultations.consent_version' => $previous]);
    }
    $result = $app->make(Modules\Raonslab\Product\Services\ConsultationService::class)->submit($validated);
    $token = trim(file_get_contents('/etc/g7-product/verification-token'));
    $response = Illuminate\Support\Facades\Http::withToken($token)->acceptJson()->timeout(15)
        ->get('http://127.0.0.1:18770/api/modules/sirsoft-board/admin/board/raon-consultations/posts/'.$result->post->id);
    $adminHttp = $response->status() === 200
        && $response->json('data.id') === $result->post->id
        && $response->json('data.category') === 'NEW';
    echo json_encode([
        'scope' => 'synthetic_internal_service_not_public_contact',
        'bootId' => $bootId,
        'leadId' => $result->post->id,
        'status' => $result->post->category,
        'admin_http' => $adminHttp,
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit($adminHttp && $result->post->is_secret ? 0 : 1);
} catch (Throwable $exception) {
    echo json_encode(['scope' => 'synthetic_internal_service_not_public_contact', 'error_class' => get_class($exception)]).PHP_EOL;
    exit(1);
}
