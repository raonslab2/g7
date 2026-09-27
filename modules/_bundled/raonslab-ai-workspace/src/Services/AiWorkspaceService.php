<?php

namespace Modules\Raonslab\Ai\Workspace\Services;

use App\Models\User;
use Modules\Raonslab\Ai\Workspace\Repositories\Contracts\AiRequestRepositoryInterface;
use Psr\Http\Message\StreamInterface;

class AiWorkspaceService
{
    public function __construct(
        private readonly AiGcsV2Adapter $adapter,
        private readonly AiRequestRepositoryInterface $requests,
    ) {}

    public function capabilities(User $user): array
    {
        $projects = $this->adapter->projects($user);
        $providers = $this->adapter->providers($user);
        $session = $this->adapter->session($user);

        return [
            'project_id' => $this->adapter->projectId(),
            'projects' => $projects['projects'] ?? $projects['items'] ?? $projects,
            'providers' => $providers['providers'] ?? $providers['items'] ?? $providers,
            'session' => $session,
        ];
    }

    public function list(User $user): array
    {
        $localIds = $this->requests->forUser($user)->pluck('request_id')->flip();
        $response = $this->adapter->listRequests($user);
        $items = $response['requests'] ?? $response['items'] ?? [];
        $owned = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item) || ! isset($item['request_id']) || ! $localIds->has((string) $item['request_id'])) {
                continue;
            }
            $this->requests->remember($user, $item);
            $owned[] = $item;
        }

        return ['requests' => $owned, 'next_cursor' => $response['next_cursor'] ?? null];
    }

    public function submit(User $user, array $input): array
    {
        $remote = $this->adapter->submit($user, $input);
        $normalized = $this->unwrapRequest($remote);
        $this->requests->remember($user, $normalized);

        return $normalized;
    }

    public function detail(User $user, string $requestId): array
    {
        $this->requests->owned($user, $requestId);
        $remote = $this->unwrapRequest($this->adapter->getRequest($user, $requestId));
        $this->requests->remember($user, $remote);

        return $remote;
    }

    public function followUp(User $user, string $requestId, array $input): array
    {
        $this->requests->owned($user, $requestId);
        $remote = $this->unwrapRequest($this->adapter->followUp($user, $requestId, $input));
        $this->requests->remember($user, $remote);

        return $remote;
    }

    public function resume(User $user, string $requestId, array $input): array
    {
        $this->requests->owned($user, $requestId);
        $remote = $this->unwrapRequest($this->adapter->resume($user, $requestId, $input));
        $this->requests->remember($user, $remote);

        return $remote;
    }

    public function events(User $user, string $requestId, int $after): StreamInterface
    {
        $this->requests->owned($user, $requestId);

        return $this->adapter->eventStream($user, $requestId, $after);
    }

    private function unwrapRequest(array $payload): array
    {
        $request = isset($payload['request']) && is_array($payload['request'])
            ? $payload['request']
            : $payload;
        if (! isset($request['request_id']) || ! is_string($request['request_id'])) {
            throw new \UnexpectedValueException('AI_GCS 응답에 request_id가 없습니다.');
        }

        return $request;
    }
}
