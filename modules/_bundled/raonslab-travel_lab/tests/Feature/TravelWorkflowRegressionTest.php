<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Extension\HookManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\InquiryEvent;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Raonslab\TravelLab\Tests\WorkflowTestCase;
use Modules\Sirsoft\Ecommerce\Http\Middleware\ResolveShippingCountry;
use Modules\Sirsoft\Ecommerce\Services\ShippingPolicyService;
use PHPUnit\Framework\Attributes\DataProvider;

/** Real models/migrations/services; SQLite evidence is not MySQL row-lock validation. */
class TravelWorkflowRegressionTest extends WorkflowTestCase
{
    private const API = '/api/modules/raonslab-travel_lab';

    private function rejected(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected a conflict before any inquiry/capacity write.');
        } catch (HttpResponseException $exception) {
            $this->assertSame(409, $exception->getResponse()->getStatusCode());
        }
    }

    /**
     * @scenario flow=same_day
     *
     * @effects known_same_day_id_rejected, no_cart_written
     */
    public function test_same_day_departure_known_id_cannot_be_added_by_http(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $departure->update(['departure_date' => now()->toDateString(), 'return_date' => now()->addDay()->toDateString()]);
        $token = $user->createToken('same-day-cart')->plainTextToken;
        Auth::forgetGuards();
        $this->postJson(self::API.'/cart', ['departure_id' => $departure->id, 'quantity' => 1], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(409);
        $this->assertDatabaseCount('ecommerce_carts', 0);
        $this->assertSame(0, $departure->fresh()->reserved);
    }

    /**
     * @scenario flow=same_day
     *
     * @effects same_day_stale_cart_visible_unavailable, same_day_submit_rejected_atomically
     */
    public function test_same_day_departure_stale_cart_is_unavailable_and_cannot_be_submitted(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 1);
        $departure->update(['departure_date' => now()->toDateString(), 'return_date' => now()->addDay()->toDateString()]);
        $token = $user->createToken('same-day-submit')->plainTextToken;
        Auth::forgetGuards();
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->getJson(self::API.'/cart', $headers)->assertOk()
            ->assertJsonPath('data.items.0.available', false)
            ->assertJsonPath('data.items.0.unavailable_reason', 'departure_unavailable');
        $this->postJson(self::API.'/inquiries', [
            'cart_ids' => [$cart['items'][0]['id']], 'contact' => ['name' => 'Synthetic'], 'idempotency_key' => 'same-day-stale',
        ], $headers)->assertStatus(409);
        $this->assertDatabaseCount('ecommerce_carts', 1);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 0);
        $this->assertSame(0, $departure->fresh()->reserved);
    }

    public static function capacityBoundaries(): array
    {
        return [
            'stock ceiling last seats' => [10, 5, 3, 2, true],
            'stock ceiling exceeded' => [10, 5, 3, 3, false],
            'original review oversell' => [10, 5, 3, 4, false],
            'capacity ceiling last seats' => [5, 10, 3, 2, true],
            'capacity ceiling exceeded' => [5, 10, 3, 3, false],
            'stock below reserved' => [10, 2, 3, 1, false],
            'stock exhausted' => [10, 3, 3, 1, false],
            'capacity exhausted' => [3, 10, 3, 1, false],
        ];
    }

    /**
     * @scenario flow=stock_ceiling
     *
     * @effects option_stock_and_capacity_share_ceiling, exact_boundary_reserved, oversell_rejected
     */
    #[DataProvider('capacityBoundaries')]
    public function test_capacity_and_stock_ceiling_on_cart_and_submit(int $capacity, int $stock, int $reserved, int $quantity, bool $accepted): void
    {
        $user = $this->createUser();
        $departure = $this->departure($capacity);
        $departure->option->update(['stock_quantity' => $stock]);
        $departure->forceFill(['reserved' => $reserved])->save();
        $service = app(TravelCartService::class);
        if (! $accepted) {
            $this->rejected(fn () => $service->add($user->id, $departure->id, $quantity));
            $this->assertDatabaseCount('ecommerce_carts', 0);
            $this->assertSame($reserved, $departure->fresh()->reserved);

            return;
        }
        $cart = $service->add($user->id, $departure->id, $quantity);
        $this->assertSame(min($capacity, $stock) - $reserved, $cart['items'][0]['remaining_capacity']);
        app(InquiryService::class)->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'capacity-boundary');
        $this->assertSame($reserved + $quantity, $departure->fresh()->reserved);
        $this->assertSame($stock, $departure->option->fresh()->stock_quantity);
    }

    /**
     * @scenario flow=stock_ceiling
     *
     * @effects stock_change_rechecked, calculation_and_reserve_rolled_back, cart_preserved
     */
    public function test_stock_reduced_after_cart_add_cannot_oversell_at_submit(): void
    {
        $user = $this->createUser();
        $departure = $this->departure(10);
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 2);
        $departure->option->update(['stock_quantity' => 2]);
        $departure->forceFill(['reserved' => 1])->save();
        $this->rejected(fn () => app(InquiryService::class)->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'reduced-stock'));
        $this->assertSame(1, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 0);
        $this->assertDatabaseCount('ecommerce_carts', 1);
    }

    /**
     * @scenario flow=shipping_policy
     *
     * @effects paid_default_not_used, missing_explicit_free_policy_rejected_before_calculation
     */
    public function test_paid_default_policy_cannot_charge_a_travel_inquiry_with_missing_policy(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 1);
        app(ShippingPolicyService::class)->create([
            'name' => ['ko' => '유료 기본', 'en' => 'Paid default'], 'is_active' => true, 'is_default' => true,
            'country_settings' => [['country_code' => 'KR', 'shipping_method' => 'custom', 'charge_policy' => 'fixed', 'base_fee' => 5000, 'extra_fee_enabled' => false, 'is_active' => true]],
        ]);
        $departure->product->update(['shipping_policy_id' => null]);
        $this->rejected(fn () => app(InquiryService::class)->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'paid-fallback'));
        $shown = app(TravelCartService::class)->get($user->id);
        $this->assertFalse($shown['items'][0]['available']);
        $this->assertSame('shipping_policy_unavailable', $shown['items'][0]['unavailable_reason']);
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
    }

    /**
     * @scenario flow=shipping_policy
     *
     * @effects country_header_not_price_input, country_context_restored, full_calculator_snapshot_persisted
     */
    public function test_country_header_does_not_change_price_and_context_is_restored(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 2);
        App::instance(ResolveShippingCountry::SHIPPING_COUNTRY_KEY, 'US');
        $token = $user->createToken('country-test')->plainTextToken;
        Auth::forgetGuards();
        $response = $this->postJson(self::API.'/inquiries', [
            'cart_ids' => [$cart['items'][0]['id']], 'contact' => ['name' => 'Synthetic'], 'idempotency_key' => 'country-header',
        ], ['Authorization' => 'Bearer '.$token, 'X-Shipping-Country' => 'JP']);
        $response->assertCreated()->assertJsonPath('data.total_amount', '24000.00');
        $inquiry = app(InquiryService::class)->findOwn($user->id, $response->json('data.id'));
        $this->assertSame(0, $inquiry->calculation_snapshot['summary']['total_shipping']);
        $this->assertSame(24000, $inquiry->calculation_snapshot['summary']['final_amount']);
        $this->assertSame('KR', $inquiry->calculation_snapshot['shipping_country']);
        $this->assertSame('US', App::make(ResolveShippingCountry::SHIPPING_COUNTRY_KEY));
        $this->assertSame($user->id, $inquiry->events->sole()->actor_id);
    }

    /**
     * @scenario flow=shipping_policy
     *
     * @effects nonzero_shipping_result_rejected, reserve_and_snapshot_not_created
     */
    public function test_calculator_shipping_or_discount_effects_are_rejected(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 1);
        HookManager::addFilter('sirsoft-ecommerce.calculation.after_final_result', static function ($result) {
            $result->summary->totalShipping = 1000;

            return $result;
        }, 99);
        $this->rejected(fn () => app(InquiryService::class)->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'shipping-result'));
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
    }

    /**
     * @scenario flow=normalized_replay
     *
     * @effects contact_normalized, replay_http_200, unchanged_request_creates_one_event
     */
    public function test_null_or_absent_phone_and_contact_whitespace_share_idempotency_hash(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 1);
        $service = app(InquiryService::class);
        $first = $service->submit($user->id, [$cart['items'][0]['id']], ['name' => ' Synthetic ', 'phone' => null], 'normalize-key');
        $second = $service->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'normalize-key');
        $this->assertSame($first->id, $second->id);
        $this->assertSame(['name' => 'Synthetic'], $second->contact);
        $this->assertSame(1, $departure->fresh()->reserved);
        $token = $user->createToken('replay-test')->plainTextToken;
        Auth::forgetGuards();
        $this->postJson(self::API.'/inquiries', [
            'cart_ids' => [$cart['items'][0]['id']], 'contact' => ['name' => 'Synthetic', 'phone' => null], 'idempotency_key' => 'normalize-key',
        ], ['Authorization' => 'Bearer '.$token])->assertOk()->assertJsonPath('data.id', $first->id);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 1);
    }

    /**
     * @scenario flow=audit
     *
     * @effects note_only_update_persisted_audited, exact_retry_noop, explicit_null_clear_audited
     */
    public function test_same_status_admin_note_edits_audit_exact_retry_once_and_can_clear(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.read', 'raonslab-travel_lab.inquiries.update']);
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 1);
        $service = app(InquiryService::class);
        $inquiry = $service->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'note-only');
        $service->transition($admin->id, $inquiry->id, InquiryStatus::TEST_INQUIRY, 'Reviewed');
        $service->transition($admin->id, $inquiry->id, InquiryStatus::TEST_INQUIRY, 'Reviewed');
        $this->assertSame('Reviewed', $inquiry->fresh()->admin_note);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 2);
        $event = InquiryEvent::query()->latest('id')->first();
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame('TEST_INQUIRY', $event->from_status);
        $this->assertSame('TEST_INQUIRY', $event->to_status);
        $token = $admin->createToken('note-clear')->plainTextToken;
        Auth::forgetGuards();
        $this->patchJson(self::API.'/admin/inquiries/'.$inquiry->id, ['status' => 'TEST_INQUIRY', 'admin_note' => null], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertNull($inquiry->fresh()->admin_note);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 3);
        $this->assertSame(1, $departure->fresh()->reserved);
    }

    /**
     * @scenario flow=status_filter
     *
     * @effects uppercase_filter_executes_real_query, state_contract_visible, owner_can_cancel_test_accepted
     */
    public function test_status_filter_resources_and_owner_cancellation_use_canonical_enum(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.read', 'raonslab-travel_lab.inquiries.update']);
        $firstDeparture = $this->departure();
        $secondDeparture = $this->departure();
        $firstCart = app(TravelCartService::class)->add($user->id, $firstDeparture->id, 2);
        $service = app(InquiryService::class);
        $first = $service->submit($user->id, [$firstCart['items'][0]['id']], ['name' => 'Synthetic'], 'filter-first');
        $secondCart = app(TravelCartService::class)->add($user->id, $secondDeparture->id, 1);
        $service->submit($user->id, [$secondCart['items'][0]['id']], ['name' => 'Synthetic'], 'filter-second');
        $service->transition($admin->id, $first->id, InquiryStatus::UNDER_REVIEW);
        $service->transition($admin->id, $first->id, InquiryStatus::TEST_ACCEPTED);
        $token = $admin->createToken('filter-test')->plainTextToken;
        Auth::forgetGuards();
        $this->getJson(self::API.'/admin/inquiries?status=TEST_ACCEPTED', ['Authorization' => 'Bearer '.$token])
            ->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $first->id)
            ->assertJsonPath('data.data.0.allowed_transitions', ['CANCELLED'])
            ->assertJsonPath('data.data.0.first_product_name', '테스트 여행')
            ->assertJsonPath('data.data.0.total_quantity', 2)->assertJsonPath('data.data.0.abilities.can_update', true);
        Auth::forgetGuards();
        $ownerToken = $user->createToken('owner-test')->plainTextToken;
        $this->getJson(self::API.'/inquiries/'.$first->id, ['Authorization' => 'Bearer '.$ownerToken])->assertOk()->assertJsonPath('data.can_cancel', true);
        $service->cancel($user->id, $first->id);
        $this->assertSame(0, $firstDeparture->fresh()->reserved);
        $this->assertSame(4, InquiryEvent::where('inquiry_id', $first->id)->count());
    }

    /**
     * @scenario flow=audit
     *
     * @effects audit_insert_failure_rolls_back_inquiry_capacity_cart, same_key_retry_after_failure
     */
    public function test_audit_insert_failure_rolls_back_the_whole_submission_and_retry_is_safe(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 2);
        DB::unprepared("CREATE TRIGGER travel_audit_failure BEFORE INSERT ON travel_lab_inquiry_events BEGIN SELECT RAISE(ABORT, 'travel_audit_test_failure'); END");
        try {
            app(InquiryService::class)->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'audit-failure');
            $this->fail('Audit failure did not roll back submission.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('travel_audit_test_failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER travel_audit_failure');
        }
        foreach (['travel_lab_inquiries', 'travel_lab_inquiry_items', 'travel_lab_inquiry_events'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('ecommerce_carts', 1);
        app(InquiryService::class)->submit($user->id, [$cart['items'][0]['id']], ['name' => 'Synthetic'], 'audit-failure');
        $this->assertSame(2, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiry_events', 1);
    }
}
