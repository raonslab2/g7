<?php

namespace Modules\Raonslab\TravelLab\Providers;

use App\Extension\BaseModuleServiceProvider;
use Illuminate\Routing\Router;
use Modules\Raonslab\TravelLab\Console\Commands\ProvisionTravelSupportCommand;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelCatalogConflictResponse;
use Modules\Raonslab\TravelLab\Repositories\CatalogRepository;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\Contracts\TravelSupportPostRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowInquiryRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\TravelSupportPostRepository;
use Modules\Raonslab\TravelLab\Repositories\WorkflowCartRepository;
use Modules\Raonslab\TravelLab\Repositories\WorkflowInquiryRepository;

class TravelLabServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'raonslab-travel_lab';

    protected array $repositories = [
        CatalogRepositoryInterface::class => CatalogRepository::class,
        WorkflowCartRepositoryInterface::class => WorkflowCartRepository::class,
        WorkflowInquiryRepositoryInterface::class => WorkflowInquiryRepository::class,
        TravelSupportPostRepositoryInterface::class => TravelSupportPostRepository::class,
    ];

    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../../config/catalog.php', 'raonslab-travel_lab.catalog');
        $this->mergeConfigFrom(__DIR__.'/../../config/support.php', 'raonslab-travel_lab.support');
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Router::class)->pushMiddlewareToGroup('api', TravelCatalogConflictResponse::class);
        if ($this->app->runningInConsole()) {
            $this->commands([ProvisionTravelSupportCommand::class]);
        }
    }
}
