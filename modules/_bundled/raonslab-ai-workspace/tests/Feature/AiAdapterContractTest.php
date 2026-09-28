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
use Modules\Raonslab\Ai\Workspace\Services\CustomerSafeAiPayload;
use Modules\Raonslab\Ai\Workspace\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use GuzzleHttp\Psr7\Utils;

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
        $adapter->shouldNotReceive('resume');
        $adapter->shouldNotReceive('eventStream');
        $requests = Mockery::mock(AiRequestRepositoryInterface::class);
        $requests->shouldReceive('owned')
            ->times(4)
            ->with($user, 'req-owned-by-user-b')
            ->andThrow((new ModelNotFoundException)->setModel('AiRequest'));
        $workspace = new AiWorkspaceService($adapter, $requests, new CustomerSafeAiPayload);

        foreach (['detail', 'followUp', 'resume', 'events'] as $method) {
            try {
                match ($method) {
                    'detail' => $workspace->detail($user, 'req-owned-by-user-b'),
                    'followUp' => $workspace->followUp($user, 'req-owned-by-user-b', ['text' => '계속']),
                    'resume' => $workspace->resume($user, 'req-owned-by-user-b', ['text' => '계속']),
                    'events' => $workspace->events($user, 'req-owned-by-user-b', 0),
                };
                $this->fail('Expected ownership verification to fail closed.');
            } catch (ModelNotFoundException) {
                $this->assertTrue(true);
            }
        }
    }

    #[Test]
    public function current_v2_request_shapes_cross_the_browser_boundary_as_an_allowlisted_dto(): void
    {
        $safe = (new CustomerSafeAiPayload)->request([
            'request_id' => 'req_safe_1',
            'user_id' => 'user-b-must-not-cross',
            'project_id' => 'GNUBOARD7',
            'provider' => 'CODEX',
            'profile' => 'CODEX_1',
            'prompt' => '고객 요청을 검토해 주세요.',
            'state' => 'COMPLETED',
            'status' => [
                'last_event_sequence' => 41,
                'waiting_reason' => '완료됨',
                'native_turn_id' => 'turn-private',
            ],
            'final_result' => [
                'output' => "검토가 완료되었습니다.\ntoken=top-secret-token\n경로 /srv/private/report.txt\ncommand: rm -rf /srv/private",
                'payload' => ['credential' => 'nested-secret', 'cwd' => '/home/operator'],
            ],
            'result' => ['turn' => ['command' => 'private shell']],
            'native_session_id' => 'session-private',
            'workspace_path' => '/home/operator/workspace',
            'agent_tools_root' => '/opt/agent-tools',
            'output_schema' => ['token' => 'schema-secret'],
            'evidence' => [['shell' => 'cat /etc/passwd']],
        ]);

        $serialized = json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->assertSame('검토가 완료되었습니다.', strtok($safe['result']['text'], "\n"));
        $this->assertSame(41, $safe['status']['last_event_sequence']);
        $this->assertStringContainsString('[credential hidden]', $serialized);
        $this->assertStringContainsString('[filesystem path hidden]', $serialized);
        $this->assertStringContainsString('[명령 원문 숨김]', $serialized);
        foreach (['top-secret-token', 'nested-secret', '/srv/private', '/home/operator', '/opt/agent-tools', 'rm -rf', 'user-b-must-not-cross', 'session-private', 'native_turn_id', 'output_schema', 'evidence'] as $private) {
            $this->assertStringNotContainsString($private, $serialized);
        }
    }

    #[Test]
    public function codex_and_claude_result_text_is_preserved_without_native_result_envelopes(): void
    {
        $presenter = new CustomerSafeAiPayload;
        $codex = $presenter->request([
            'request_id' => 'req_codex',
            'state' => 'COMPLETED',
            'result' => ['text' => 'CODEX 고객 결과', 'turn' => ['id' => 'native-turn', 'usage' => ['tokens' => 99]]],
        ]);
        $claude = $presenter->request([
            'request_id' => 'req_claude',
            'state' => 'COMPLETED',
            'result' => ['result' => 'CLAUDE 고객 결과', 'session_id' => 'native-session', 'usage' => ['cost' => 1]],
        ]);

        $this->assertSame(['text' => 'CODEX 고객 결과'], $codex['result']);
        $this->assertSame(['text' => 'CLAUDE 고객 결과'], $claude['result']);
        $this->assertArrayNotHasKey('turn', $codex['result']);
        $this->assertArrayNotHasKey('session_id', $claude['result']);
    }

    #[Test]
    public function detail_messages_and_resume_all_return_the_same_customer_safe_request_contract(): void
    {
        $user = new User(['uuid' => '5f72e0c0-8b89-4d5f-8c44-a5ba69048993']);
        $user->id = 102;
        $adapter = Mockery::mock(AiGcsV2Adapter::class);
        $raw = fn (string $text): array => [
            'request_id' => 'req_same_contract',
            'state' => 'COMPLETED',
            'status' => ['last_event_sequence' => 8, 'command' => 'private'],
            'result' => ['text' => $text, 'native' => ['token' => 'inner-secret']],
            'workspace_path' => '/srv/private/worktree',
            'native_session_id' => 'session-secret',
        ];
        $adapter->shouldReceive('getRequest')->once()->andReturn($raw('detail result'));
        $adapter->shouldReceive('followUp')->once()->andReturn($raw('message result'));
        $adapter->shouldReceive('resume')->once()->andReturn($raw('resume result'));
        $requests = Mockery::mock(AiRequestRepositoryInterface::class);
        $requests->shouldReceive('owned')->times(3)->with($user, 'req_same_contract');
        $requests->shouldReceive('remember')->times(3)->with($user, Mockery::type('array'));
        $workspace = new AiWorkspaceService($adapter, $requests, new CustomerSafeAiPayload);

        $responses = [
            $workspace->detail($user, 'req_same_contract'),
            $workspace->followUp($user, 'req_same_contract', ['text' => 'continue', 'idempotency_key' => 'same-key']),
            $workspace->resume($user, 'req_same_contract', ['text' => 'resume', 'idempotency_key' => 'same-key']),
        ];

        $this->assertSame(['detail result', 'message result', 'resume result'], array_map(
            fn (array $response): string => $response['result']['text'],
            $responses
        ));
        foreach ($responses as $response) {
            $serialized = json_encode($response, JSON_THROW_ON_ERROR);
            $this->assertSame(8, $response['status']['last_event_sequence']);
            $this->assertStringNotContainsString('inner-secret', $serialized);
            $this->assertStringNotContainsString('/srv/private', $serialized);
            $this->assertStringNotContainsString('session-secret', $serialized);
            $this->assertArrayNotHasKey('command', $response['status']);
        }
    }

    #[Test]
    public function sse_is_rebuilt_from_allowlisted_events_without_echoing_raw_chunks(): void
    {
        $raw = ': upstream heartbeat with token=heartbeat-secret'."\n"
            .'id: 72'."\n"
            .'event: provider.result'."\n"
            .'data: '.json_encode([
                'sequence' => 72,
                'event_type' => 'provider.result',
                'created_at' => '2026-09-28T03:00:00Z',
                'payload' => [
                    'state' => 'COMPLETED',
                    'token' => 'stream-secret',
                    'cwd' => '/srv/private',
                    'command' => 'bash -c private',
                    'native' => ['credential' => 'nested-stream-secret'],
                ],
            ], JSON_THROW_ON_ERROR)."\n\n";

        $frames = implode('', iterator_to_array((new CustomerSafeAiPayload)->sanitizedSse(Utils::streamFor($raw))));

        $this->assertStringContainsString("id: 72\n", $frames);
        $this->assertStringContainsString("event: provider.result\n", $frames);
        $this->assertStringContainsString('"sequence":72', $frames);
        $this->assertStringContainsString('"state":"COMPLETED"', $frames);
        foreach (['heartbeat-secret', 'stream-secret', 'nested-stream-secret', '/srv/private', 'bash -c private', 'upstream heartbeat'] as $private) {
            $this->assertStringNotContainsString($private, $frames);
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
        $eventController = file_get_contents($root.'/src/Http/Controllers/Api/AiEventController.php');
        $this->assertStringContainsString('sanitizedSse', $eventController);
        $this->assertStringNotContainsString('echo $chunk', $eventController);
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
