<?php

namespace Modules\Raonslab\Ai\Workspace\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Raonslab\Ai\Workspace\Repositories\AiRequestRepository;
use Modules\Raonslab\Ai\Workspace\Repositories\Contracts\AiRequestRepositoryInterface;

class AiWorkspaceServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'raonslab-ai-workspace';

    protected array $repositories = [
        AiRequestRepositoryInterface::class => AiRequestRepository::class,
    ];
}
