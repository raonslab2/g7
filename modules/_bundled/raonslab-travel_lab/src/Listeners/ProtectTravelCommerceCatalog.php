<?php

namespace Modules\Raonslab\TravelLab\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use Illuminate\Validation\ValidationException;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelCatalogConflictResponse;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Sirsoft\Ecommerce\Exceptions\ProductHasOrderHistoryException;
use Modules\Sirsoft\Ecommerce\Models\Product;

/** Native deletion/sync is rejected before irreversible file/Q&A cleanup or option writes. */
class ProtectTravelCommerceCatalog implements HookListenerInterface
{
    public function __construct(private WorkflowCartRepositoryInterface $repository) {}

    public static function getSubscribedHooks(): array
    {
        return [
            'sirsoft-ecommerce.product.before_delete' => ['method' => 'beforeDelete', 'sync' => true, 'priority' => 1],
            'sirsoft-ecommerce.product.before_update' => ['method' => 'beforeUpdate', 'sync' => true, 'priority' => 1],
            // Recheck the final filtered payload before ProductService performs any writes.
            'sirsoft-ecommerce.product.filter_update_data' => ['method' => 'filterUpdate', 'type' => 'filter', 'priority' => PHP_INT_MAX],
        ];
    }

    /** Default action handler; explicit subscriptions select the methods below. */
    public function handle(...$args): void
    {
        if (($args[0] ?? null) instanceof Product) {
            $this->beforeDelete($args[0]);
        }
    }

    public function beforeDelete(Product $product): void
    {
        if ($this->repository->containsTravelCommerceItems([(int) $product->id], [])) {
            // The module response adapter preserves a truthful travel-specific reason.
            app('request')->attributes->set(TravelCatalogConflictResponse::ATTRIBUTE, true);
            throw new ProductHasOrderHistoryException(0, __('raonslab-travel_lab::workflow.product_delete_restricted'));
        }
    }

    public function beforeUpdate(Product $product, array $data): void
    {
        if (! isset($data['options']) || ! is_array($data['options'])) {
            return;
        }
        $retained = array_map('intval', array_column($data['options'], 'id'));
        if (array_diff($this->repository->linkedOptionIds((int) $product->id), $retained) !== []) {
            // Native update catches ValidationException and returns 422 + field reasons.
            throw ValidationException::withMessages(['options' => __('raonslab-travel_lab::workflow.option_delete_restricted')]);
        }
    }

    public function filterUpdate(array $data, Product $product): array
    {
        $this->beforeUpdate($product, $data);

        return $data;
    }
}
