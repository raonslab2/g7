<?php

namespace Modules\Raonslab\Product\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Raonslab\Product\Console\Commands\SeedQaContentCommand;
use Modules\Raonslab\Product\Repositories\ConsultationRepository;
use Modules\Raonslab\Product\Repositories\Contracts\ConsultationRepositoryInterface;

class ProductServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'raonslab-product';

    protected array $repositories = [
        ConsultationRepositoryInterface::class => ConsultationRepository::class,
    ];

    protected array $commands = [
        SeedQaContentCommand::class,
    ];

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }
}
