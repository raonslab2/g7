<?php

namespace Modules\Raonslab\TravelLab\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Raonslab\TravelLab\Repositories\CatalogRepository;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;

class TravelLabServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'raonslab-travel_lab';

    protected array $repositories = [CatalogRepositoryInterface::class => CatalogRepository::class];

    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../../config/catalog.php', 'raonslab-travel_lab.catalog');
    }
}
