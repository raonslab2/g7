<?php

namespace Modules\Raonslab\TravelLab\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;
use Modules\Sirsoft\Ecommerce\Enums\ProductDisplayStatus;
use Modules\Sirsoft\Ecommerce\Enums\ProductSalesStatus;
use Modules\Sirsoft\Ecommerce\Enums\ProductTaxStatus;
use Modules\Sirsoft\Ecommerce\Services\ProductService;
use Modules\Sirsoft\Ecommerce\Services\ShippingPolicyService;

/** 샘플 전용 삽입 헬퍼. 기존 상품·재고·수정 내용·문의 확보 인원을 덮어쓰지 않습니다. */
class SyntheticCatalogHelper
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly ProductService $products,
        private readonly ShippingPolicyService $shippingPolicies,
    ) {}

    public function sync(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            $policy = $this->catalog->findSampleShippingPolicy() ?? $this->shippingPolicies->create([
                'name' => ['ko' => '트래블 랩 배송 없는 테스트', 'en' => 'Travel Lab synthetic nonshipping'],
                'is_default' => false, 'is_active' => true, 'sort_order' => 9999,
                // 국가 설정 없는 명시 정책: 기본 배송정책으로 폴백하지 않고 배송비 0.
                'country_settings' => [],
            ]);
            foreach ($rows as $row) {
                $product = $this->catalog->findSampleProduct($row['sku']);
                if ($product === null) {
                    $product = $this->products->create([
                        'product_code' => $this->products->generateUniqueCode(),
                        'sku' => $row['sku'], 'name' => $row['name'],
                        'description' => $row['summary'], 'description_mode' => 'text',
                        'selling_price' => $row['price'], 'list_price' => $row['price'],
                        'sales_status' => ProductSalesStatus::ON_SALE->value,
                        'display_status' => ProductDisplayStatus::VISIBLE->value,
                        'tax_status' => ProductTaxStatus::TAX_FREE->value,
                        'shipping_policy_id' => $policy->id, 'has_options' => true,
                        'min_purchase_qty' => 1, 'max_purchase_qty' => 12,
                        'options' => array_map(fn ($departure) => [
                            'option_code' => $departure['code'],
                            'option_name' => ['ko' => $departure['departure_date'].' 출발', 'en' => $departure['departure_date'].' departure'],
                            'option_values' => [['key' => ['ko' => '출발일', 'en' => 'Departure'], 'value' => ['ko' => $departure['departure_date'], 'en' => $departure['departure_date']]]],
                            'price_adjustment' => $departure['adjustment'],
                            'stock_quantity' => $departure['capacity'],
                            'is_active' => true, 'is_default' => false,
                        ], $row['departures']),
                    ]);
                }
                // 운영자가 소프트 삭제한 샘플은 재생성·복구하지 않습니다.
                if ($product->trashed()) {
                    continue;
                }
                $this->catalog->seedMetadata($product->id, [
                    'region' => $row['region'], 'theme' => $row['theme'],
                    'duration_days' => $row['duration_days'], 'summary' => $row['summary'],
                    'itinerary' => $row['itinerary'], 'published' => true,
                ]);
                foreach ($row['departures'] as $data) {
                    $option = $product->options->firstWhere('option_code', $data['code']);
                    if ($option !== null) {
                        $this->catalog->seedDeparture($product->id, $option->id, [
                            'departure_date' => $data['departure_date'], 'return_date' => $data['return_date'],
                            'capacity' => $data['capacity'], 'is_active' => true,
                        ]);
                    }
                }
            }
        });
    }
}
