<?php

namespace Modules\Raonslab\Ai\Workspace\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Raonslab\Ai\Workspace\Exceptions\AiGcsException;
use Modules\Raonslab\Ai\Workspace\Services\AiGcsV2Adapter;
use Modules\Raonslab\Ai\Workspace\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\Test;

class AiAdapterContractTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('raonslab-ai-workspace', [
            'base_url' => 'http://127.0.0.1:18771',
            'proxy_token' => 'test-only-proxy-token',
            'proxy_token_file' => '/tmp/g7-ai-workspace-test-token-does-not-exist',
            'project_id' => 'GNUBOARD7',
            'operator_id' => 'g7-adapter',
            'origin' => 'http://127.0.0.1:18770',
            'connect_timeout' => 1,
            'request_timeout' => 2,
        ]);
    }

    #[Test]
    public function submit_uses_server_owned_project_and_identity_headers(): void
    {
        Http::fake([
            '*' => Http::response([
                'request_id' => 'req_contract_1',
                'project_id' => 'GNUBOARD7',
                'state' => 'ACCEPTED',
            ], 202),
        ]);
        $user = new User(['uuid' => '5f72e0c0-8b89-4d5f-8c44-a5ba69048993']);

        $result = app(AiGcsV2Adapter::class)->submit($user, [
            'provider' => 'CODEX',
            'profile' => 'CODEX_1',
            'prompt' => '현재 SHA만 확인하세요.',
            'attachment_ids' => [],
            'idempotency_key' => 'contract-key',
        ]);

        $this->assertSame('req_contract_1', $result['request_id']);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://127.0.0.1:18771/api/v1/requests'
                && $request->header('X-GCS-Authenticated-User')[0] === 'gnuboard7:5f72e0c0-8b89-4d5f-8c44-a5ba69048993'
                && $request->header('X-AgentOpt-V2-Proxy-Token')[0] === 'test-only-proxy-token'
                && $request['project_id'] === 'GNUBOARD7';
        });
    }

    #[Test]
    public function adapter_fails_closed_without_server_secret(): void
    {
        config()->set('raonslab-ai-workspace.proxy_token', '');
        $user = new User(['uuid' => '5f72e0c0-8b89-4d5f-8c44-a5ba69048993']);

        $this->expectException(AiGcsException::class);
        $this->expectExceptionMessage('AI 서비스 인증이 구성되지 않았습니다.');

        app(AiGcsV2Adapter::class)->providers($user);
    }

    #[Test]
    public function module_contains_no_forbidden_agentopt_storage_access(): void
    {
        $root = dirname(__DIR__, 2);
        $source = '';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src')) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $source .= file_get_contents($file->getPathname());
            }
        }

        $this->assertDoesNotMatchRegularExpression('/agentopt-v2\.sqlite|SELECT\s+.*requests|provider session file/i', $source);
        $this->assertStringContainsString("['auth:sanctum'", file_get_contents($root.'/src/routes/api.php'));
        $this->assertStringContainsString('data-request-id', file_get_contents($root.'/resources/layouts/user/ai_workspace.json'));
    }
}
