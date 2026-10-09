<?php

namespace Modules\Raonslab\TravelLab\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Sirsoft\Ecommerce\Models\Cart;
use Modules\Sirsoft\Ecommerce\Models\OrderOption;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Models\ShippingPolicy;
use Modules\Sirsoft\Ecommerce\Models\ShippingPolicyCountrySetting;

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
        // 잠금으로 읽은 모델 자체를 사용한다. REPEATABLE READ의 오래된 일반 SELECT로
        // 다시 load하면 대기 뒤 최신 재고·가격 대신 이전 snapshot을 읽을 수 있다.
        $productQuery = Product::query()->whereIn('id', $departures->pluck('product_id'))->orderBy('id');
        $products = ($lock ? $productQuery->lockForUpdate() : $productQuery)->get()->keyBy('id');
        $optionQuery = ProductOption::query()->whereIn('id', $departures->pluck('product_option_id'))->orderBy('id');
        $options = ($lock ? $optionQuery->lockForUpdate() : $optionQuery)->get()->keyBy('id');
        $policyQuery = ShippingPolicy::query()->whereIn('id', $products->pluck('shipping_policy_id')->filter())->orderBy('id');
        $policies = ($lock ? $policyQuery->lockForUpdate() : $policyQuery)->get()->keyBy('id');
        $countryQuery = ShippingPolicyCountrySetting::query()->whereIn('shipping_policy_id', $policies->keys())->orderBy('id');
        $countries = ($lock ? $countryQuery->lockForUpdate() : $countryQuery)->get()->groupBy('shipping_policy_id');
        foreach ($policies as $policy) {
            $policy->setRelation('countrySettings', $countries->get($policy->id, new Collection));
        }
        foreach ($products as $product) {
            $product->setRelation('shippingPolicy', $policies->get($product->shipping_policy_id));
        }
        foreach ($departures as $departure) {
            $product = $products->get($departure->product_id);
            $option = $options->get($departure->product_option_id);
            if ($option !== null) {
                $option->setRelation('product', $product);
            }
            $departure->setRelation('product', $product);
            $departure->setRelation('option', $option);
        }

        return $departures;
    }

    public function publishedProductIds(array $productIds, bool $lock = false): array
    {
        $query = TravelProduct::query()->whereIn('product_id', $productIds)->orderBy('product_id');
        $rows = ($lock ? $query->lockForUpdate() : $query)->get(['product_id', 'published']);

        return $rows->where('published', true)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    public function reserve(Departure $departure, int $quantity): bool
    {
        // option은 departure와 함께 잠긴 현재 행이다. 같은 ceiling을 조건부 UPDATE에도 적용한다.
        $ceiling = min((int) $departure->capacity, (int) $departure->option?->stock_quantity);
        if ($quantity < 1) {
            return false;
        }
        $changed = Departure::query()->whereKey($departure->id)
            ->whereRaw('reserved + ? <= capacity', [$quantity])
            ->whereRaw('reserved + ? <= ?', [$quantity, $ceiling])->increment('reserved', $quantity);
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

    public function containsTravelCommerceItems(array $productIds, array $optionIds): bool
    {
        return TravelProduct::query()->whereIn('product_id', $productIds)->exists()
            || Departure::query()->whereIn('product_option_id', $optionIds)->exists();
    }

    public function orderCommerceItems(int $orderId): array
    {
        return OrderOption::query()->where('order_id', $orderId)->get(['product_id', 'product_option_id'])->all();
    }
}
