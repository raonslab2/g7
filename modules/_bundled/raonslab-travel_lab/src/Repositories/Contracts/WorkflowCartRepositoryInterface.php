<?php

namespace Modules\Raonslab\TravelLab\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Raonslab\TravelLab\Models\Departure;

interface WorkflowCartRepositoryInterface
{
    public function user(int $userId, bool $lock = false): ?User;

    public function carts(int $userId, ?array $ids = null, bool $lock = false): Collection;

    public function departure(int $id, bool $lock = false): ?Departure;

    public function departuresForOptions(array $optionIds, bool $lock = false): Collection;

    public function departuresByIds(array $ids, bool $lock = false): Collection;

    public function publishedProductIds(array $productIds, bool $lock = false): array;

    public function containsTravelCommerceItems(array $productIds, array $optionIds): bool;

    /** Departure-linked options which a native full options sync must retain. */
    public function linkedOptionIds(int $productId): array;

    public function orderCommerceItems(int $orderId): array;

    public function reserve(Departure $departure, int $quantity): bool;

    public function release(Departure $departure, int $quantity): bool;
}
