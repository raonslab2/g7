<?php

namespace Modules\Raonslab\Product\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Raonslab\Product\Adapters\G7BoardConsultationAdapter;
use Modules\Raonslab\Product\Contracts\ConsultationBoardGateway;

class ProductServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'raonslab-product';

    protected array $repositories = [
        ConsultationBoardGateway::class => G7BoardConsultationAdapter::class,
    ];
}
