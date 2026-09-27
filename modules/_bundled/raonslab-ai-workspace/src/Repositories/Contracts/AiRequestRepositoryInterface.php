<?php

namespace Modules\Raonslab\Ai\Workspace\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Raonslab\Ai\Workspace\Models\AiRequest;

interface AiRequestRepositoryInterface
{
    public function remember(User $user, array $remote): AiRequest;

    public function owned(User $user, string $requestId): AiRequest;

    /** @return Collection<int, AiRequest> */
    public function forUser(User $user, int $limit = 50): Collection;
}
