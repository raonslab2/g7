<?php

namespace Modules\Raonslab\TravelLab\Repositories;

use App\Search\KeywordSearch;
use App\Support\Query\BoundedPaginator;
use App\Support\Query\PaginationLimits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Enums\CatalogSort;
use Modules\Raonslab\TravelLab\Exceptions\CatalogConflictException;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\InquiryItem;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;
use Modules\Sirsoft\Ecommerce\Enums\ProductDisplayStatus;
use Modules\Sirsoft\Ecommerce\Enums\ProductSalesStatus;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Models\ShippingPolicy;

class CatalogRepository implements CatalogRepositoryInterface
{
    /** 이커머스 getSellingPrice()와 같은 현재 가격 식이며 여행 가격을 저장하지 않습니다. */
    private const OPTION_PRICE_SQL = 'ecommerce_products.selling_price + ecommerce_product_options.price_adjustment';

    private function eligibleDepartures(Builder $query, array $filters = []): void
    {
        $query->where('travel_lab_departures.is_active', true)
            ->where('departure_date', '>=', now()->toDateString())
            ->whereColumn('capacity', '>', 'reserved')
            ->whereHas('option', function (Builder $option) use ($filters) {
                $option->where('ecommerce_product_options.is_active', true)
                    ->whereColumn('ecommerce_product_options.product_id', 'travel_lab_departures.product_id')
                    ->whereColumn('ecommerce_product_options.stock_quantity', '>=', 'travel_lab_departures.capacity');
                if (isset($filters['min_price']) || isset($filters['max_price'])) {
                    $option->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_product_options.product_id');
                    if (isset($filters['min_price'])) {
                        $option->whereRaw(self::OPTION_PRICE_SQL.' >= ?', [$filters['min_price']]);
                    }
                    if (isset($filters['max_price'])) {
                        $option->whereRaw(self::OPTION_PRICE_SQL.' <= ?', [$filters['max_price']]);
                    }
                }
            });
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (! empty($filters[$key])) {
                $query->where('departure_date', $operator, $filters[$key]);
            }
        }
    }

    private function catalogQuery(bool $public, array $filters = []): Builder
    {
        $query = TravelProduct::query()->whereHas('product', function (Builder $product) use ($public) {
            if ($public) {
                $product->where('display_status', ProductDisplayStatus::VISIBLE->value)
                    ->where('sales_status', ProductSalesStatus::ON_SALE->value);
            }
        });
        if ($public) {
            $query->where('published', true)->whereHas('departures', fn (Builder $departure) => $this->eligibleDepartures($departure, $filters));
        }

        return $query;
    }

    public function paginate(array $filters, bool $public = true): LengthAwarePaginator
    {
        $query = $this->catalogQuery($public, $filters);
        foreach (['region', 'theme'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $query->whereHas('product', fn (Builder $product) => KeywordSearch::applyAny($product, ['name', 'description'], $filters['q']));
        }
        $query->with(['product.images', 'departures' => function ($departure) use ($public, $filters) {
            if ($public) {
                $this->eligibleDepartures($departure->getQuery(), $filters);
            }
            $departure->with('option.product')->orderBy('departure_date')->orderBy('id');
        }]);

        $sort = CatalogSort::tryFrom($filters['sort'] ?? '') ?? CatalogSort::RECOMMENDED;
        if ($sort !== CatalogSort::RECOMMENDED) {
            $departure = Departure::query()->whereColumn('travel_lab_departures.product_id', 'travel_lab_products.product_id');
            $this->eligibleDepartures($departure, $filters);
            if ($sort === CatalogSort::DEPARTURE_ASC) {
                $departure->selectRaw('MIN(departure_date)');
            } else {
                $departure->join('ecommerce_product_options', 'ecommerce_product_options.id', '=', 'travel_lab_departures.product_option_id')
                    ->join('ecommerce_products', 'ecommerce_products.id', '=', 'travel_lab_departures.product_id')
                    ->selectRaw('MIN('.self::OPTION_PRICE_SQL.')');
            }
            $query->addSelect(['travel_lab_products.*', 'catalog_sort_value' => $departure])
                ->orderBy('catalog_sort_value', $sort === CatalogSort::PRICE_DESC ? 'desc' : 'asc');
        }
        $query->orderBy('travel_lab_products.id'); // 같은 가격·날짜에도 페이지 경계를 고정합니다.

        return BoundedPaginator::paginate($query, min(48, max(1, (int) ($filters['per_page'] ?? 12))), (int) ($filters['page'] ?? 1), PaginationLimits::resultCap('travel_lab.catalog'));
    }

    public function find(int $productId, bool $public = true): TravelProduct
    {
        return $this->catalogQuery($public)->where('product_id', $productId)
            ->with(['product.images', 'departures' => function ($query) use ($public) {
                if ($public) {
                    $this->eligibleDepartures($query->getQuery());
                }
                $query->with('option.product')->orderBy('departure_date')->orderBy('id');
            }])->firstOrFail();
    }

    public function departures(int $productId, bool $public = true): Collection
    {
        return $this->find($productId, $public)->departures;
    }

    public function facets(): array
    {
        // 분류는 설정의 유한 목록으로 제한하여 응답이 무제한으로 커지지 않게 합니다.
        $result = [];
        foreach (['region' => 'regions', 'theme' => 'themes'] as $field => $config) {
            $allowed = config('raonslab-travel_lab.catalog.'.$config, []);
            $result[$field] = $this->catalogQuery(true)->whereIn($field, $allowed)
                ->select($field)->distinct()->orderBy($field)->limit(count($allowed))->pluck($field)->all();
        }

        return $result;
    }

    public function saveDeparture(int $productId, array $data, ?int $departureId = null): Departure
    {
        return DB::transaction(function () use ($productId, $data, $departureId) {
            TravelProduct::query()->where('product_id', $productId)->lockForUpdate()->firstOrFail();
            $departure = $departureId === null ? new Departure : Departure::query()->where('product_id', $productId)->lockForUpdate()->findOrFail($departureId);
            $option = ProductOption::query()->where('product_id', $productId)->lockForUpdate()->findOrFail($data['product_option_id']);
            if ($option->stock_quantity < $data['capacity'] || $data['capacity'] < ($departure->reserved ?? 0)) {
                throw new CatalogConflictException('messages.capacity_conflict');
            }
            if ($departure->exists && $departure->product_option_id !== $option->id) {
                throw new CatalogConflictException('messages.option_immutable');
            }
            if (Departure::query()->where('product_option_id', $option->id)->when($departure->exists, fn ($query) => $query->where('id', '!=', $departure->id))->exists()) {
                throw new CatalogConflictException('messages.option_in_use');
            }
            $changedDates = $departure->exists && ($departure->departure_date->toDateString() !== $data['departure_date'] || $departure->return_date->toDateString() !== $data['return_date']);
            if ($changedDates && (($departure->reserved ?? 0) > 0 || InquiryItem::query()->where('departure_id', $departure->id)->exists())) {
                throw new CatalogConflictException('messages.dates_in_use');
            }
            $departure->fill([...$data, 'product_id' => $productId]);
            $departure->save();

            return $departure->refresh()->load('option.product');
        });
    }

    public function updateMetadata(int $productId, array $data): TravelProduct
    {
        $travel = $this->find($productId, false);
        $travel->update($data);

        return $travel;
    }

    public function findSampleProduct(string $code): ?Product
    {
        return Product::withTrashed()->with('options')->where('sku', $code)->first();
    }

    public function findSampleShippingPolicy(): ?ShippingPolicy
    {
        return ShippingPolicy::query()->where('name->en', 'Travel Lab synthetic nonshipping')->first();
    }

    public function seedMetadata(int $productId, array $data): TravelProduct
    {
        return TravelProduct::firstOrCreate(['product_id' => $productId], $data);
    }

    public function seedDeparture(int $productId, int $optionId, array $data): Departure
    {
        return Departure::firstOrCreate(['product_option_id' => $optionId], [...$data, 'product_id' => $productId]);
    }
}
