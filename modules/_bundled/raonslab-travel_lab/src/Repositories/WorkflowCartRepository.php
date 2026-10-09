<?php

namespace Modules\Raonslab\TravelLab\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Sirsoft\Ecommerce\Models\Cart;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;

/** 여행 워크플로의 읽기·잠금과 모의 정원 변경을 소유한다. 커머스 쓰기는 하지 않는다. */
class WorkflowCartRepository implements WorkflowCartRepositoryInterface
{
    public function user(int $userId, bool $lock = false): ?User
    {
        $query = User::query()->whereKey($userId);

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    public function carts(int $userId, ?array $ids = null, bool $lock = false): Collection
    {
        $query = Cart::query()->where('user_id', $userId)
            ->whereIn('product_option_id', Departure::query()->select('product_option_id'))
            ->orderBy('id');
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    public function departure(int $id, bool $lock = false): ?Departure
    {
        return $this->loadCommerce($this->departuresByIds([$id], $lock), $lock)->first();
    }

    public function departuresForOptions(array $optionIds, bool $lock = false): Collection
    {
        $query = Departure::query()->whereIn('product_option_id', $optionIds)->orderBy('id');
        $departures = ($lock ? $query->lockForUpdate() : $query)->get();

        return $this->loadCommerce($departures, $lock);
    }

    public function departuresByIds(array $ids, bool $lock = false): Collection
    {
        $query = Departure::query()->whereIn('id', $ids)->orderBy('id');

        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    private function loadCommerce(Collection $departures, bool $lock): Collection
    {
        // 가격·활성 상태도 같은 트랜잭션 안에서 고정한다. 모든 잠금은 ID 오름차순이다.
        if ($lock) {
            Product::query()->whereIn('id', $departures->pluck('product_id'))->orderBy('id')->lockForUpdate()->get();
            ProductOption::query()->whereIn('id', $departures->pluck('product_option_id'))->orderBy('id')->lockForUpdate()->get();
        }

        return $departures->load(['product', 'option']);
    }

    public function publishedProductIds(array $productIds, bool $lock = false): array
    {
        $query = TravelProduct::query()->whereIn('product_id', $productIds)->orderBy('product_id');
        $rows = ($lock ? $query->lockForUpdate() : $query)->get(['product_id', 'published']);

        return $rows->where('published', true)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    public function reserve(Departure $departure, int $quantity): bool
    {
        // 행 잠금과 조건부 UPDATE를 함께 사용하여 정원 상한을 DB에서도 보장한다.
        $changed = Departure::query()->whereKey($departure->id)
            ->whereRaw('reserved + ? <= capacity', [$quantity])->increment('reserved', $quantity);
        if ($changed === 1) {
            $departure->reserved += $quantity;
        }

        return $changed === 1;
    }

    public function release(Departure $departure, int $quantity): bool
    {
        $changed = Departure::query()->whereKey($departure->id)
            ->where('reserved', '>=', $quantity)->decrement('reserved', $quantity);
        if ($changed === 1) {
            $departure->reserved -= $quantity;
        }

        return $changed === 1;
    }
}
