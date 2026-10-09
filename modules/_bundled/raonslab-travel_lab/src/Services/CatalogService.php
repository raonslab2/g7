<?php

namespace Modules\Raonslab\TravelLab\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;

class CatalogService
{
    public function __construct(private readonly CatalogRepositoryInterface $repository) {}

    public function index(array $filters, bool $public = true): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $public);
    }

    public function find(int $productId, bool $public = true): TravelProduct
    {
        return $this->repository->find($productId, $public);
    }

    public function departures(int $productId, bool $public = true): Collection
    {
        return $this->repository->departures($productId, $public);
    }

    public function facets(): array
    {
        return $this->repository->facets();
    }

    public function saveDeparture(int $productId, array $data, ?int $departureId = null): Departure
    {
        return $this->repository->saveDeparture($productId, $data, $departureId);
    }

    public function updateMetadata(int $productId, array $data): TravelProduct
    {
        return $this->repository->updateMetadata($productId, $data);
    }
}
