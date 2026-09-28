<?php

namespace Modules\Raonslab\Ai\Workspace\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Mockery;
use Modules\Raonslab\Ai\Workspace\Exceptions\AiGcsException;
use Modules\Raonslab\Ai\Workspace\Repositories\Contracts\AiRequestRepositoryInterface;
use Modules\Raonslab\Ai\Workspace\Services\AiGcsV2Adapter;
use Modules\Raonslab\Ai\Workspace\Services\AiWorkspaceService;
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
    public function upstream_private_error_detail_never_crosses_the_g7_api_boundary(): void
    {
        Http::fake([
            '*' => Http::response([
                'detail' => 'token=secret command=/bin/sh cwd=/srv/private',
            ], 500),
        ]);
        $user = new User(['uuid' => '5f72e0c0-8b89-4d5f-8c44-a5ba69048993']);

        try {
            app(AiGcsV2Adapter::class)->providers($user);
            $this->fail('Expected a sanitized AiGcsException.');
        } catch (AiGcsException $exception) {
            $this->assertSame(502, $exception->status);
            $this->assertSame('AIGCS_HTTP_500', $exception->errorCode);
            $this->assertSame(
                'AI 서비스를 현재 사용할 수 없습니다. 잠시 후 다시 시도해 주세요.',
                $exception->getMessage()
            );
            $this->assertStringNotContainsString('secret', $exception->getMessage());
            $this->assertStringNotContainsString('/srv/private', $exception->getMessage());
        }
    }

    #[Test]
    public function foreign_request_is_rejected_before_remote_detail_or_follow_up_access(): void
    {
        $user = new User(['uuid' => '5f72e0c0-8b89-4d5f-8c44-a5ba69048993']);
        $user->id = 101;
        $adapter = Mockery::mock(AiGcsV2Adapter::class);
        $adapter->shouldNotReceive('getRequest');
        $adapter->shouldNotReceive('followUp');
        $requests = Mockery::mock(AiRequestRepositoryInterface::class);
        $requests->shouldReceive('owned')
            ->twice()
            ->with($user, 'req-owned-by-user-b')
            ->andThrow((new ModelNotFoundException)->setModel('AiRequest'));
        $workspace = new AiWorkspaceService($adapter, $requests);

        foreach (['detail', 'followUp'] as $method) {
            try {
                $method === 'detail'
                    ? $workspace->detail($user, 'req-owned-by-user-b')
                    : $workspace->followUp($user, 'req-owned-by-user-b', ['text' => '계속']);
                $this->fail('Expected ownership verification to fail closed.');
            } catch (ModelNotFoundException) {
                $this->assertTrue(true);
            }
        }
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
        $this->assertStringContainsString("'permission:user,raonslab-ai-workspace.requests.use'", file_get_contents($root.'/src/routes/api.php'));
        $this->assertStringContainsString('data-request-id', file_get_contents($root.'/resources/layouts/user/ai_workspace.json'));
    }

    #[Test]
    public function user_workspace_routes_use_the_g7_typed_route_location(): void
    {
        $root = dirname(__DIR__, 2);
        $routesPath = $root.'/resources/routes/user.json';

        $this->assertFileExists($routesPath);
        $this->assertFileDoesNotExist($root.'/resources/routes.json');

        $routes = json_decode((string) file_get_contents($routesPath), true, 512, JSON_THROW_ON_ERROR);
        $paths = array_column($routes['routes'], 'path');

        $this->assertContains('*/ai', $paths);
        $this->assertContains('*/ai/requests/:request_id', $paths);
    }
}
