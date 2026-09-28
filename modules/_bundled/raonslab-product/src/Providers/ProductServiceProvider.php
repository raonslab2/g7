<?php

namespace Modules\Raonslab\Product\Providers;

use App\Extension\BaseModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\Product\Adapters\G7BoardConsultationAdapter;
use Modules\Raonslab\Product\Console\Commands\BootstrapNativePagesCommand;
use Modules\Raonslab\Product\Console\Commands\ConsultationReadinessCommand;
use Modules\Raonslab\Product\Console\Commands\ConsultationRehearsalCommand;
use Modules\Raonslab\Product\Console\Commands\RemediateInfoPagesCommand;
use Modules\Raonslab\Product\Console\Commands\SeedQaContentCommand;
use Modules\Raonslab\Product\Contracts\ConsultationBoardGateway;

class ProductServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'raonslab-product';

    protected array $repositories = [
        ConsultationBoardGateway::class => G7BoardConsultationAdapter::class,
    ];

    protected array $commands = [
        BootstrapNativePagesCommand::class,
        RemediateInfoPagesCommand::class,
        SeedQaContentCommand::class,
        ConsultationReadinessCommand::class,
        ConsultationRehearsalCommand::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // ModuleRouteServiceProvider prefixes normal module web routes with
        // /modules/{identifier}. These compatibility URLs predate the native
        // Page URLs and must stay at the application root. A compiled route
        // collection already contains them, so do not register duplicates.
        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(dirname(__DIR__).'/routes/compatibility.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }
}
