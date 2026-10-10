<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Raonslab\TravelLab\Tests\WorkflowTestCase;
use Modules\Sirsoft\Ecommerce\Models\Cart;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Models\ShippingPolicy;
use Modules\Sirsoft\Ecommerce\Models\ShippingPolicyCountrySetting;

/** Actual SQLite effects and executed Eloquent builders; not an InnoDB contention test. */
class InquiryIdempotencyGapRegressionTest extends WorkflowTestCase
{
    private function cart(User $user, Departure $departure, int $quantity = 1): int
    {
        app(TravelCartService::class)->add($user->id, $departure->id, $quantity);

        return (int) Cart::where('user_id', $user->id)->where('product_option_id', $departure->product_option_id)->sole()->id;
    }

    /** Observe the actual executed builders without replacing repositories or SQL execution. */
    private function observeReads(callable $operation): array
    {
        $scopes = User::getAllGlobalScopes();
        $reads = [];
        foreach ([User::class, Inquiry::class, Cart::class, Departure::class, Product::class, ProductOption::class, ShippingPolicy::class, ShippingPolicyCountrySetting::class, TravelProduct::class] as $model) {
            $model::addGlobalScope('idempotency-gap-observer', static function (Builder $query) use (&$reads): void {
                $query->getQuery()->beforeQuery(static function (QueryBuilder $query) use (&$reads): void {
                    $reads[] = [
                        'table' => $query->from,
                        'lock' => $query->lock,
                        'transaction' => $query->getConnection()->transactionLevel(),
                        // Compilation only: no MySQL connection, credentials or external schema.
                        'mysql_sql' => (new MySqlGrammar($query->getConnection()))->compileSelect($query),
                    ];
                });
            });
        }
        try {
            $operation();
        } finally {
            User::setAllGlobalScopes($scopes);
        }

        return $reads;
    }

    public function test_missing_key_lookup_does_not_lock_an_index_gap_but_keeps_user_and_native_price_locks(): void
    {
        $user = $this->createUser();
        $departure = $this->departure(2);
        $cartId = $this->cart($user, $departure, 2);
        $departure->product->update(['selling_price' => 17000]);
        $departure->option->update(['price_adjustment' => 700]);
        $inquiry = null;

        $reads = $this->observeReads(function () use ($user, $cartId, &$inquiry): void {
            $inquiry = app(InquiryService::class)->submit($user->id, [$cartId], ['name' => '합성 회원'], 'missing-index-key');
        });

        $this->assertSame('users', $reads[0]['table']);
        $this->assertTrue($reads[0]['lock']);
        $this->assertSame(1, $reads[0]['transaction']);
        $this->assertStringContainsString('for update', $reads[0]['mysql_sql']);
        $this->assertSame('travel_lab_inquiries', $reads[1]['table']);
        $this->assertNotSame(true, $reads[1]['lock'], 'Missing-key reads must not acquire an InnoDB next-key/gap lock.');
        $this->assertStringNotContainsString('for update', $reads[1]['mysql_sql']);
        $this->assertSame(1, $reads[1]['transaction']);
        foreach (['ecommerce_carts', 'travel_lab_departures', 'ecommerce_products', 'ecommerce_product_options', 'ecommerce_shipping_policies', 'ecommerce_shipping_policy_country_settings'] as $table) {
            $locked = array_values(array_filter($reads, static fn (array $read): bool => $read['table'] === $table && $read['lock'] === true));
            $this->assertNotEmpty($locked, $table.' must retain its authoritative row lock.');
            $this->assertSame(1, $locked[0]['transaction']);
            $this->assertStringContainsString('for update', $locked[0]['mysql_sql']);
        }
        $this->assertInstanceOf(Inquiry::class, $inquiry);
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $inquiry->status);
        $this->assertEquals(35400, $inquiry->total_amount);
        $this->assertEquals(17700, $inquiry->items->sole()->unit_price);
        $this->assertSame(2, $departure->fresh()->reserved);
        $this->assertDatabaseMissing('ecommerce_carts', ['id' => $cartId]);
        $this->assertDatabaseCount('travel_lab_inquiries', 1);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 1);
        $this->assertDatabaseCount('ecommerce_orders', 0);
        $this->assertDatabaseCount('ecommerce_order_payments', 0);
    }

    public function test_same_key_is_owner_scoped_and_replay_after_cancellation_has_no_reservation_or_pricing_side_effects(): void
    {
        $first = $this->createUser();
        $second = $this->createUser();
        $departure = $this->departure(2);
        $firstCart = $this->cart($first, $departure);
        $secondCart = $this->cart($second, $departure);
        $service = app(InquiryService::class);
        $one = $service->submit($first->id, [$firstCart], ['name' => '합성 회원'], 'owner-scoped-key');
        $two = $service->submit($second->id, [$secondCart], ['name' => '합성 회원'], 'owner-scoped-key');
        $this->assertNotSame($one->id, $two->id);
        $this->assertSame(2, $departure->fresh()->reserved);
        $service->cancel($first->id, $one->id);
        $departure->product->update(['selling_price' => 1]);
        $replay = null;
        $reads = $this->observeReads(function () use ($service, $first, $firstCart, &$replay): void {
            $replay = $service->submit($first->id, [$firstCart], ['name' => ' 합성 회원 '], 'owner-scoped-key');
        });

        $this->assertSame($one->id, $replay->id);
        $this->assertSame(InquiryStatus::CANCELLED, $replay->status);
        $this->assertEquals(12000, $replay->total_amount);
        $this->assertCount(2, $replay->events);
        $this->assertSame(['users', 'travel_lab_inquiries'], array_column($reads, 'table'));
        $this->assertTrue($reads[0]['lock']);
        $this->assertNotSame(true, $reads[1]['lock']);
        try {
            $service->submit($first->id, [$firstCart], ['name' => '다른 합성 입력'], 'owner-scoped-key');
            $this->fail('A changed payload must retain its idempotency conflict.');
        } catch (HttpResponseException $e) {
            $this->assertSame(409, $e->getResponse()->getStatusCode());
        }
        $this->assertSame(1, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 2);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 3);
        $this->assertDatabaseCount('ecommerce_carts', 0);
        $this->assertDatabaseCount('ecommerce_orders', 0);
        $this->assertDatabaseCount('ecommerce_order_payments', 0);
    }
}
