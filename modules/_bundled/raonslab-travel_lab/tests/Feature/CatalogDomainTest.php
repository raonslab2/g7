<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Raonslab\TravelLab\Services\CatalogService;
use Modules\Raonslab\TravelLab\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CatalogDomainTest extends ModuleTestCase
{
    public static function hiddenProducts(): array
    {
        return [
            'unpublished' => [['published' => false], [], [], []],
            'commerce-hidden' => [[], ['display_status' => 'hidden'], [], []],
            'commerce-suspended' => [[], ['sales_status' => 'suspended'], [], []],
            'departure-inactive' => [[], [], ['is_active' => false], []],
            'departure-past' => [[], [], ['departure_date' => '2026-10-08', 'return_date' => '2026-10-10'], []],
            'departure-full' => [[], [], ['reserved' => 20], []],
            'option-inactive' => [[], [], [], ['is_active' => false]],
            'option-understock' => [[], [], [], ['stock_quantity' => 0]],
        ];
    }

    /**
     * @scenario visibility=unpublished
     * @scenario visibility=commerce-hidden
     * @scenario visibility=commerce-suspended
     * @scenario visibility=departure-inactive
     * @scenario visibility=departure-past
     * @scenario visibility=departure-full
     * @scenario visibility=option-inactive
     * @scenario visibility=option-understock
     *
     * @effects invisible_products_excluded, invisible_detail_not_found
     */
    #[DataProvider('hiddenProducts')]
    public function test_visibility_is_shared_by_list_detail_and_departures(array $travel, array $product, array $departure, array $option): void
    {
        [$hidden] = $this->createTravel($travel, $product, $departure, $option);
        [$visible] = $this->createTravel();
        $service = $this->app->make(CatalogService::class);
        $this->assertSame([$visible->product_id], $service->index([])->getCollection()->pluck('product_id')->all());
        foreach (['find', 'departures'] as $method) {
            try {
                $service->{$method}($hidden->product_id);
                $this->fail('Invisible catalog product exposed via '.$method);
            } catch (ModelNotFoundException) {
                $this->assertTrue(true);
            }
        }
    }

    /** @scenario visibility=commerce-deleted
     * @effects invisible_products_excluded, invisible_detail_not_found
     */
    public function test_soft_deleted_commerce_product_is_not_exposed(): void
    {
        [$travel] = $this->createTravel();
        $travel->product->delete();
        $service = $this->app->make(CatalogService::class);
        $this->assertSame(0, $service->index([])->total());
        $this->expectException(ModelNotFoundException::class);
        $service->find($travel->product_id);
    }

    /** @scenario visibility=visible
     * @effects ecommerce_identity_preserved, ecommerce_option_price_used, capacity_reserved_preserved
     */
    public function test_identity_relations_and_option_price_remain_commerce_owned(): void
    {
        [$travel, $departure] = $this->createTravel([], ['selling_price' => 150000], ['reserved' => 3], ['price_adjustment' => 25000]);
        $service = $this->app->make(CatalogService::class);
        $detail = $service->find($travel->product_id);
        $this->assertSame($travel->product_id, $detail->product->id);
        $this->assertSame($departure->product_option_id, $detail->departures->first()->option->id);
        $this->assertEquals(175000, $detail->departures->first()->option->getSellingPrice());
        $this->assertSame(17, $departure->capacity - $departure->reserved);
    }

    /** @effects filters_applied, ecommerce_option_price_used, stable_sort_and_pagination */
    public function test_region_theme_keyword_date_and_price_filters_and_all_sort_orders(): void
    {
        [$first] = $this->createTravel();
        [$second] = $this->createTravel(['region' => 'busan', 'theme' => 'culture'], ['name' => ['ko' => '부산 역사', 'en' => 'Busan heritage'], 'selling_price' => 200000], ['departure_date' => '2026-12-01', 'return_date' => '2026-12-03']);
        [$third] = $this->createTravel(['region' => 'jeju', 'theme' => 'culture'], ['selling_price' => 300000], ['departure_date' => '2026-11-10', 'return_date' => '2026-11-12']);
        $service = $this->app->make(CatalogService::class);
        $ids = fn (array $filters) => $service->index($filters)->getCollection()->pluck('product_id')->all();
        $this->assertSame([$first->product_id], $ids(['region' => 'jeju', 'theme' => 'nature']));
        $this->assertSame([$second->product_id], $ids(['q' => 'Busan']));
        $this->assertSame([$first->product_id], $ids(['date_from' => '2026-11-01', 'date_to' => '2026-11-01']));
        $this->assertSame([$second->product_id], $ids(['min_price' => 150000, 'max_price' => 250000]));
        $this->assertSame([$first->product_id, $second->product_id, $third->product_id], $ids(['sort' => 'price_asc']));
        $this->assertSame([$third->product_id, $second->product_id, $first->product_id], $ids(['sort' => 'price_desc']));
        $this->assertSame([$first->product_id, $third->product_id, $second->product_id], $ids(['sort' => 'departure_asc']));
        $recommended = $ids(['sort' => 'recommended']);
        $this->assertCount(3, $recommended);
        $this->assertSame(array_slice($recommended, 1, 1), $ids(['sort' => 'recommended', 'per_page' => 1, 'page' => 2]));
    }

    /** @effects zero_keyword_applied */
    public function test_zero_keyword_is_a_real_search_term(): void
    {
        [$matching] = $this->createTravel([], ['name' => ['ko' => '여행 0', 'en' => 'Travel 0']]);
        $this->createTravel([], ['name' => ['ko' => '바다 여행', 'en' => 'Sea journey']]);
        $result = $this->app->make(CatalogService::class)->index(['q' => '0']);
        $this->assertSame([$matching->product_id], $result->getCollection()->pluck('product_id')->all());
        $this->assertSame(1, $result->total());
    }

    /** @effects filters_applied */
    public function test_date_and_price_constraints_must_match_the_same_departure(): void
    {
        [$travel, $departure] = $this->createTravel([], ['selling_price' => 100000]);
        $option = $departure->option->replicate();
        $option->option_code = 'SECOND';
        $option->price_adjustment = 200000;
        $option->save();
        $other = $departure->replicate();
        $other->product_option_id = $option->id;
        $other->departure_date = '2026-12-01';
        $other->return_date = '2026-12-03';
        $other->save();
        $service = $this->app->make(CatalogService::class);
        $this->assertSame(0, $service->index(['date_from' => '2026-12-01', 'max_price' => 150000])->total());
        $this->assertSame([$travel->product_id], $service->index(['date_from' => '2026-12-01', 'min_price' => 250000])->getCollection()->pluck('product_id')->all());
    }

    /** @effects departures_eligible_only */
    public function test_departure_list_excludes_full_past_inactive_and_understock_rows(): void
    {
        [$travel, $valid] = $this->createTravel();
        foreach ([['reserved' => 20], ['departure_date' => '2026-10-08'], ['is_active' => false], ['stock_quantity' => 0]] as $index => $attributes) {
            $option = $valid->option->replicate();
            $option->option_code = 'EXCLUDED-'.$index;
            if (isset($attributes['stock_quantity'])) {
                $option->stock_quantity = $attributes['stock_quantity'];
                unset($attributes['stock_quantity']);
            }
            $option->save();
            $departure = $valid->replicate();
            $departure->product_option_id = $option->id;
            $departure->forceFill($attributes)->save();
        }
        $result = $this->app->make(CatalogService::class)->departures($travel->product_id);
        $this->assertSame([$valid->id], $result->pluck('id')->all());
    }

    /** @effects effective_stock_capacity, same_day_unavailable */
    public function test_reduced_stock_limits_available_seats_without_hiding_remaining_seats(): void
    {
        [$travel] = $this->createTravel([], [], ['capacity' => 10, 'reserved' => 3], ['stock_quantity' => 5]);
        $departure = $this->app->make(CatalogService::class)->find($travel->product_id)->departures->first();
        $this->assertSame(2, $departure->available);
        $departure->option->update(['stock_quantity' => 3]);
        $this->assertSame(0, $this->app->make(CatalogService::class)->index([])->total());
        $this->createTravel([], [], ['departure_date' => '2026-10-09', 'return_date' => '2026-10-10']);
        $this->assertSame(0, $this->app->make(CatalogService::class)->index([])->total());
    }

    /** @effects visible_facets_only */
    public function test_facets_exclude_unpublished_and_unavailable_products(): void
    {
        $this->createTravel();
        $this->createTravel(['region' => 'secret', 'theme' => 'hidden', 'published' => false]);
        $this->createTravel(['region' => 'expired', 'theme' => 'past'], [], ['departure_date' => '2026-01-01']);
        $this->assertSame(['region' => ['jeju'], 'theme' => ['nature']], $this->app->make(CatalogService::class)->facets());
    }
}
