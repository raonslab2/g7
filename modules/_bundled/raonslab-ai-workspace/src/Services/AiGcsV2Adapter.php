<?php

namespace Modules\Raonslab\Ai\Workspace\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Raonslab\Ai\Workspace\Exceptions\AiGcsException;
use Psr\Http\Message\StreamInterface;

/**
 * AI_GCS V2의 현재 Request API만 소비하는 얇은 outbound Adapter입니다.
 * AgentOpt DB·파일·Provider 세션은 어떤 경우에도 직접 읽지 않습니다.
 */
class AiGcsV2Adapter
{
    public function projects(User $user): array
    {
        return $this->json($user, 'GET', '/api/v1/projects?details=true');
    }

    public function providers(User $user): array
    {
        return $this->json($user, 'GET', '/api/v1/providers');
    }

    public function session(User $user): array
    {
        return $this->json($user, 'GET', '/api/v1/session');
    }

    public function listRequests(User $user, int $limit = 50): array
    {
        $query = http_build_query([
            'project_id' => $this->projectId(),
            'limit' => $limit,
        ]);

        return $this->json($user, 'GET', '/api/v1/requests?'.$query);
    }

    public function getRequest(User $user, string $requestId): array
    {
        return $this->json($user, 'GET', '/api/v1/requests/'.rawurlencode($requestId));
    }

    public function submit(User $user, array $input): array
    {
        return $this->json($user, 'POST', '/api/v1/requests', [
            'project_id' => $this->projectId(),
            'provider' => $input['provider'],
            'profile' => $input['profile'],
            'prompt' => $input['prompt'],
            'attachment_ids' => $input['attachment_ids'] ?? [],
            'idempotency_key' => $input['idempotency_key'],
        ]);
    }

    public function followUp(User $user, string $requestId, array $input): array
    {
        return $this->json(
            $user,
            'POST',
            '/api/v1/requests/'.rawurlencode($requestId).'/messages',
            $input
        );
    }

    public function resume(User $user, string $requestId, array $input): array
    {
        return $this->json(
            $user,
            'POST',
            '/api/v1/requests/'.rawurlencode($requestId).'/resume',
            $input
        );
    }

    public function eventStream(User $user, string $requestId, int $after): StreamInterface
    {
        try {
            $response = $this->client($user)
                ->withHeaders(['Accept' => 'text/event-stream'])
                ->withOptions(['stream' => true])
                ->timeout(0)
                ->get('/api/v1/requests/'.rawurlencode($requestId).'/events', ['after' => $after]);
        } catch (AiGcsException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new AiGcsException('AI 서비스의 이벤트 채널에 연결할 수 없습니다.', 503, 'AIGCS_UNAVAILABLE');
        }

        if (! $response->successful()) {
            $this->throwForResponse($response);
        }

        return $response->toPsrResponse()->getBody();
    }

    public function projectId(): string
    {
        return (string) config('raonslab-ai-workspace.project_id', 'GNUBOARD7');
    }

    private function json(User $user, string $method, string $path, array $payload = []): array
    {
        try {
            $request = $this->client($user)->acceptJson();
            $response = $method === 'GET'
                ? $request->get($path)
                : $request->send($method, $path, ['json' => $payload]);
        } catch (AiGcsException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new AiGcsException('AI 서비스에 연결할 수 없습니다. 잠시 후 다시 시도해 주세요.', 503, 'AIGCS_UNAVAILABLE');
        }

        if (! $response->successful()) {
            $this->throwForResponse($response);
        }

        $decoded = $response->json();
        if (! is_array($decoded)) {
            throw new AiGcsException('AI 서비스가 올바른 응답을 반환하지 않았습니다.', 502, 'AIGCS_INVALID_RESPONSE');
        }

        return $decoded;
    }

    private function client(User $user): PendingRequest
    {
        $token = (string) config('raonslab-ai-workspace.proxy_token', '');
        if ($token === '') {
            $tokenFile = (string) config(
                'raonslab-ai-workspace.proxy_token_file',
                '/etc/g7-product/ai-gcs-v2-token'
            );
            if ($tokenFile !== '' && is_readable($tokenFile)) {
                $token = trim((string) file_get_contents($tokenFile));
            }
        }
        if ($token === '') {
            throw new AiGcsException('AI 서비스 인증이 구성되지 않았습니다.', 503, 'AIGCS_NOT_CONFIGURED');
        }

        $headers = [
            'X-GCS-Authenticated-User' => 'gnuboard7:'.(string) $user->uuid,
            'X-GCS-Operator' => (string) config('raonslab-ai-workspace.operator_id', 'g7-adapter'),
            'X-AgentOpt-V2-Proxy-Token' => $token,
        ];
        $origin = (string) config('raonslab-ai-workspace.origin', '');
        if ($origin !== '') {
            $headers['Origin'] = $origin;
        }

        return Http::baseUrl((string) config('raonslab-ai-workspace.base_url'))
            ->connectTimeout((int) config('raonslab-ai-workspace.connect_timeout', 3))
            ->timeout((int) config('raonslab-ai-workspace.request_timeout', 30))
            ->withHeaders($headers);
    }

    private function throwForResponse(Response $response): never
    {
        $payload = $response->json();
        $detail = is_array($payload) ? ($payload['detail'] ?? $payload['message'] ?? null) : null;
        $message = is_string($detail) && $detail !== ''
            ? $detail
            : 'AI 서비스 요청을 처리하지 못했습니다.';
        $status = in_array($response->status(), [400, 401, 403, 404, 409, 422, 429], true)
            ? $response->status()
            : 502;

        throw new AiGcsException($message, $status, 'AIGCS_HTTP_'.$response->status());
    }
}
