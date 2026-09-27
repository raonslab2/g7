<?php

namespace Modules\Raonslab\Ai\Workspace\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Modules\Raonslab\Ai\Workspace\Models\AiRequest;
use Modules\Raonslab\Ai\Workspace\Repositories\Contracts\AiRequestRepositoryInterface;

class AiRequestRepository implements AiRequestRepositoryInterface
{
    public function remember(User $user, array $remote): AiRequest
    {
        return AiRequest::query()->updateOrCreate(
            ['request_id' => (string) $remote['request_id']],
            [
                'user_id' => $user->getKey(),
                'user_uuid' => (string) $user->uuid,
                'project_id' => (string) ($remote['project_id'] ?? config('raonslab-ai-workspace.project_id')),
                'provider' => (string) ($remote['provider'] ?? ''),
                'profile' => (string) ($remote['profile'] ?? 'default'),
                'state' => (string) ($remote['state'] ?? $remote['status'] ?? 'ACCEPTED'),
                'title' => isset($remote['title']) ? (string) $remote['title'] : null,
                'last_observed_at' => now(),
            ]
        );
    }

    public function owned(User $user, string $requestId): AiRequest
    {
        $model = AiRequest::query()
            ->where('user_id', $user->getKey())
            ->where('request_id', $requestId)
            ->first();

        if ($model === null) {
            throw (new ModelNotFoundException)->setModel(AiRequest::class, [$requestId]);
        }

        return $model;
    }

    public function forUser(User $user, int $limit = 50): Collection
    {
        return AiRequest::query()
            ->where('user_id', $user->getKey())
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
