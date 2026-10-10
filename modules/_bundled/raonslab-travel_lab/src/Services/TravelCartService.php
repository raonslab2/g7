<?php

namespace Modules\Raonslab\TravelLab\Services;

use App\Helpers\ResponseHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Raonslab\TravelLab\Support\TravelDate;
use Modules\Sirsoft\Ecommerce\Enums\ChargePolicyEnum;
use Modules\Sirsoft\Ecommerce\Exceptions\CartOperationException;
use Modules\Sirsoft\Ecommerce\Exceptions\CartQuantityLimitException;
use Modules\Sirsoft\Ecommerce\Exceptions\CartUnavailableException;
use Modules\Sirsoft\Ecommerce\Http\Middleware\ResolveShippingCountry;
use Modules\Sirsoft\Ecommerce\Services\CartService;
use Modules\Sirsoft\Ecommerce\Services\CurrencyConversionService;

/** 실제 커머스 카트를 여행 출발일에 연결한다. 가격·카트 쓰기는 커머스 서비스가 소유한다. */
class TravelCartService
{
    public function __construct(
        private WorkflowCartRepositoryInterface $repository,
        private CartService $commerce,
        private CurrencyConversionService $currency,
    ) {}

    public function get(int $userId): array
    {
        $this->requireUser($userId);
        $carts = $this->repository->carts($userId);
        $departures = $this->repository->departuresForOptions($carts->pluck('product_option_id')->all());

        return $this->calculate($userId, $carts, $departures, strict: false);
    }

    public function add(int $userId, int $departureId, int $quantity): array
    {
        return DB::transaction(function () use ($userId, $departureId, $quantity) {
            $this->requireUser($userId, true);
            if ($quantity < 1) {
                $this->fail('capacity_unavailable');
            }
            $carts = $this->repository->carts($userId, lock: true);
            $departure = $this->repository->departure($departureId, true);
            if (! $departure) {
                $this->fail('not_found', 404);
            }
            $existing = $carts->where('product_option_id', $departure->product_option_id);
            $this->assertAvailable($departure, $quantity + (int) $existing->sum('quantity'));
            $this->commerceOperation(fn () => $this->commerce->bulkAddToCart([
                'user_id' => $userId,
                'cart_key' => null,
                'product_id' => $departure->product_id,
                'items' => [['product_option_id' => $departure->product_option_id, 'quantity' => $quantity]],
            ]));

            return $this->get($userId);
        }, 3);
    }

    public function update(int $userId, int $cartId, int $quantity): array
    {
        return DB::transaction(function () use ($userId, $cartId, $quantity) {
            $this->requireUser($userId, true);
            $carts = $this->repository->carts($userId, [$cartId], true);
            if ($carts->count() !== 1) {
                $this->fail('not_found', 404);
            }
            $departures = $this->repository->departuresForOptions($carts->pluck('product_option_id')->all(), true);
            $this->assertCartSelection($carts, $departures);
            $this->assertAvailable($departures->first(), $quantity);
            $this->commerceOperation(fn () => $this->commerce->updateQuantity($cartId, $quantity, $userId, null));

            return $this->get($userId);
        }, 3);
    }

    public function remove(int $userId, int $cartId): array
    {
        return DB::transaction(function () use ($userId, $cartId) {
            $this->requireUser($userId, true);
            if ($this->repository->carts($userId, [$cartId], true)->count() !== 1) {
                $this->fail('not_found', 404);
            }
            $this->removeSelected($userId, [$cartId]);

            return $this->get($userId);
        }, 3);
    }

    /** 문의 선택은 엄격하게 검증하고, 조회는 이용 불가 항목도 남겨 삭제를 가능하게 한다. */
    public function calculate(int $userId, Collection $carts, Collection $departures, bool $lock = false, bool $strict = true): array
    {
        $published = $this->repository->publishedProductIds($departures->pluck('product_id')->all(), $lock);
        $byOption = $departures->keyBy('product_option_id');
        $optionCounts = $carts->countBy('product_option_id');
        $reasons = [];
        foreach ($carts as $cart) {
            if ((int) $cart->user_id !== $userId) {
                $this->fail('not_found', 404);
            }
            $departure = $byOption->get($cart->product_option_id);
            $reason = (! $departure || (int) $departure->product_id !== (int) $cart->product_id
                || ! empty($cart->additional_option_selections) || $optionCounts->get($cart->product_option_id) !== 1)
                ? 'unsupported_cart' : $this->unavailableReason($departure, (int) $cart->quantity, $published);
            if ($strict && $reason !== null) {
                $this->fail($reason);
            }
            $reasons[$cart->id] = $reason;
        }
        $eligible = $carts->filter(fn ($cart) => $reasons[$cart->id] === null);
        // 시험 문의는 배송국가/쿠폰/포인트를 소비하지 않는다. 실제 커머스 계산기를 쓰되
        // 국가 입력을 서버가 고정하고 원래 요청 컨텍스트는 반드시 복구한다.
        $countryKey = ResolveShippingCountry::SHIPPING_COUNTRY_KEY;
        $countryWasBound = App::bound($countryKey);
        $previousCountry = $countryWasBound ? App::make($countryKey) : null;
        App::instance($countryKey, 'KR');
        try {
            $result = $this->commerceOperation(fn () => $this->commerce->getCartWithCalculation(
                $userId, null, [], 0, $eligible->modelKeys()
            ));
        } finally {
            if ($countryWasBound) {
                App::instance($countryKey, $previousCountry);
            } else {
                App::forgetInstance($countryKey);
            }
        }
        $calculated = collect($result->calculation->items);
        $summary = $result->calculation->summary;
        if ($calculated->count() !== $eligible->count() || $result->calculation->hasValidationErrors()
            || $summary->totalShipping !== 0 || $summary->totalDiscount !== 0
            || $summary->shippingDiscount !== 0 || $summary->pointsUsed !== 0) {
            $this->fail('calculation_changed');
        }
        $items = [];
        foreach ($carts as $cart) {
            $amount = null;
            if ($reasons[$cart->id] === null) {
                $matches = $calculated->filter(fn ($item) => $item->productId === (int) $cart->product_id
                    && $item->productOptionId === (int) $cart->product_option_id
                    && $item->quantity === (int) $cart->quantity);
                if ($matches->count() !== 1) {
                    $this->fail('calculation_changed');
                }
                $amount = $matches->first();
            }
            $departure = $byOption->get($cart->product_option_id);
            $items[] = [
                'id' => (int) $cart->id,
                'departure_id' => $departure ? (int) $departure->id : null,
                'product_id' => (int) $cart->product_id,
                'product_option_id' => (int) $cart->product_option_id,
                'quantity' => (int) $cart->quantity,
                'remaining_capacity' => $departure ? max(0, min((int) $departure->capacity, (int) $departure->option?->stock_quantity) - (int) $departure->reserved) : 0,
                'product_name' => $departure?->product?->name,
                'departure_date' => $departure ? Carbon::parse($departure->departure_date)->toDateString() : null,
                'return_date' => $departure ? Carbon::parse($departure->return_date)->toDateString() : null,
                'unit_price' => $amount?->unitPrice,
                'line_total' => $amount?->finalAmount,
                'available' => $reasons[$cart->id] === null,
                'unavailable_reason' => $reasons[$cart->id],
            ];
        }

        return [
            'items' => $items,
            'totals' => $result->calculation->summary->toArray(),
            'currency_code' => $this->currency->getDefaultCurrency(),
            'calculation_snapshot' => [
                ...$result->calculation->toArray(),
                'currency_code' => $this->currency->getDefaultCurrency(),
                'shipping_country' => 'KR',
            ],
        ];
    }

    private function assertCartSelection(Collection $carts, Collection $departures): void
    {
        if ($carts->pluck('product_option_id')->unique()->count() !== $carts->count()) {
            $this->fail('unsupported_cart');
        }
        $byOption = $departures->keyBy('product_option_id');
        foreach ($carts as $cart) {
            $departure = $byOption->get($cart->product_option_id);
            if (! $departure || (int) $departure->product_id !== (int) $cart->product_id
                || ! empty($cart->additional_option_selections)) {
                $this->fail('unsupported_cart');
            }
        }
    }

    private function assertAvailable(Departure $departure, int $quantity, ?array $published = null): void
    {
        $published ??= $this->repository->publishedProductIds([(int) $departure->product_id], true);
        $reason = $this->unavailableReason($departure, $quantity, $published);
        if ($reason !== null) {
            $this->fail($reason);
        }
    }

    private function unavailableReason(Departure $departure, int $quantity, array $published): ?string
    {
        if (! in_array((int) $departure->product_id, $published, true)
            || ! $departure->is_active || ! $departure->product?->isPurchasable()
            || ! $departure->option?->is_active
            || (int) $departure->option->product_id !== (int) $departure->product_id
            || Carbon::parse($departure->departure_date)->toDateString() <= TravelDate::today()->toDateString()
            || Carbon::parse($departure->return_date)->startOfDay()->lt(Carbon::parse($departure->departure_date)->startOfDay())) {
            return 'departure_unavailable';
        }
        $policy = $departure->product->shippingPolicy;
        if ($policy === null || ! $policy->is_active || $policy->is_default
            || $policy->countrySettings->isEmpty()
            || ! $policy->countrySettings->contains(fn ($country) => $country->country_code === 'KR' && $country->is_active)
            || $policy->countrySettings->contains(fn ($country) => $country->charge_policy !== ChargePolicyEnum::FREE
                || $country->extra_fee_enabled || (float) $country->base_fee !== 0.0 || ! empty($country->api_endpoint))) {
            return 'shipping_policy_unavailable';
        }
        if ($quantity < 1 || $quantity > (int) config('sirsoft-ecommerce.cart.max_quantity', 99)
            || $quantity > max(0, min((int) $departure->capacity, (int) $departure->option->stock_quantity) - (int) $departure->reserved)) {
            return 'capacity_unavailable';
        }

        return null;
    }

    public function removeSelected(int $userId, array $cartIds): void
    {
        DB::transaction(function () use ($userId, $cartIds) {
            $this->requireUser($userId, true);
            if ($this->repository->carts($userId, $cartIds, true)->count() !== count($cartIds)) {
                $this->fail('cart_changed');
            }
            $deleted = $this->commerceOperation(fn () => $this->commerce->deleteItems($cartIds, $userId, null));
            if ($deleted !== count($cartIds)) {
                $this->fail('cart_changed');
            }
        }, 3);
    }

    private function requireUser(int $userId, bool $lock = false): void
    {
        if (! $this->repository->user($userId, $lock)) {
            $this->fail('unauthorized', 401);
        }
    }

    private function commerceOperation(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (CartOperationException|CartUnavailableException|CartQuantityLimitException $exception) {
            throw new HttpResponseException(ResponseHelper::error(
                'raonslab-travel_lab::workflow.commerce_rejected', 409, ['detail' => $exception->getMessage()]
            ));
        }
    }

    private function fail(string $key, int $status = 409): never
    {
        throw new HttpResponseException(ResponseHelper::error('raonslab-travel_lab::workflow.'.$key, $status));
    }
}
