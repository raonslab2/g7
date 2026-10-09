<?php

namespace Modules\Raonslab\TravelLab\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use Illuminate\Support\Collection;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Sirsoft\Ecommerce\Exceptions\CartUnavailableException;
use Modules\Sirsoft\Ecommerce\Models\Order;
use Modules\Sirsoft\Ecommerce\Models\TempOrder;

/** 여행 상품은 native checkout/order/payment로 전환할 수 없다. */
class BlockTravelCommerceCheckout implements HookListenerInterface
{
    public function __construct(private WorkflowCartRepositoryInterface $repository) {}

    public static function getSubscribedHooks(): array
    {
        return [
            'sirsoft-ecommerce.temp_order.before_create' => ['method' => 'handle', 'sync' => true, 'priority' => 1],
            'sirsoft-ecommerce.temp_order.before_update' => ['method' => 'handle', 'sync' => true, 'priority' => 1],
            'sirsoft-ecommerce.order.before_create' => ['method' => 'handle', 'sync' => true, 'priority' => 1],
            'sirsoft-ecommerce.order.before_payment_complete' => ['method' => 'handle', 'sync' => true, 'priority' => 1],
        ];
    }

    public function handle(...$args): void
    {
        $source = $args[0] ?? null;
        $items = match (true) {
            $source instanceof TempOrder => collect($source->items ?? [])->merge($source->calculation_input['items'] ?? []),
            $source instanceof Order => collect($this->repository->orderCommerceItems((int) $source->id)),
            $source instanceof Collection => $source,
            default => collect(),
        };
        $productIds = $items->map(static fn ($item) => (int) data_get($item, 'product_id'))->filter()->unique()->all();
        $optionIds = $items->map(static fn ($item) => (int) data_get($item, 'product_option_id'))->filter()->unique()->all();
        if ($this->repository->containsTravelCommerceItems($productIds, $optionIds)) {
            // Native controllers already map this domain exception to purchase rejection,
            // before creating temporary orders, orders, payments or stock deductions.
            throw new CartUnavailableException(__('raonslab-travel_lab::workflow.checkout_not_allowed'), [
                ['reason' => 'restricted', 'name' => __('raonslab-travel_lab::workflow.checkout_not_allowed')],
            ]);
        }
    }
}
