<?php
// AWS 운영 DB를 읽기 전용으로 검증합니다. 공개 상담 접수 허용 여부와 분리합니다.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$post = Modules\Sirsoft\Board\Models\Post::where('title', 'RAON-93AE41D9FB3E808FBAD7203315')->first();
$tokenPath = '/etc/g7-product/verification-token';
$adminResponse = Illuminate\Support\Facades\Http::withToken(trim(file_get_contents($tokenPath)))
    ->acceptJson()->timeout(15)->get('http://127.0.0.1:18770/api/modules/sirsoft-board/admin/board/raon-consultations/posts/2');
$checks = [
    'admin_http' => $adminResponse->status() === 200 && $adminResponse->json('data.id') === 2 && $adminResponse->json('data.category') === 'CLOSED',
    'database' => Illuminate\Support\Facades\DB::select('SELECT 1 AS healthy')[0]->healthy === 1,
    'lead_persisted' => $post !== null && $post->id === 2,
    'lead_private' => $post?->is_secret === true,
    'lead_status' => $post?->category === 'CLOSED',
    'public_intake_disabled' => config('raonslab-product-consultations.enabled') === false,
];
echo json_encode(['checks' => $checks, 'lead_id' => $post?->id, 'reference' => $post?->title, 'boot_id' => trim(file_get_contents('/proc/sys/kernel/random/boot_id'))], JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(in_array(false, $checks, true) ? 1 : 0);
