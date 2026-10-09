<?php

namespace Modules\Raonslab\TravelLab\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ShippingPolicy;

interface CatalogRepositoryInterface
{
    public function paginate(array $filters, bool $public = true): LengthAwarePaginator;

    public function find(int $productId, bool $public = true): TravelProduct;

    public function departures(int $productId, bool $public = true): Collection;

    public function facets(): array;

    public function saveDeparture(int $productId, array $data, ?int $departureId = null): Departure;

    public function updateMetadata(int $productId, array $data): TravelProduct;

    public function findSampleProduct(string $code): ?Product;

    public function findSampleShippingPolicy(): ?ShippingPolicy;

    public function seedMetadata(int $productId, array $data): TravelProduct;

    public function seedDeparture(int $productId, int $optionId, array $data): Departure;
}
