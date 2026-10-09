<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Helpers\PermissionHelper;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Http\Resources\InquiryResource;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Raonslab\TravelLab\Tests\WorkflowTestCase;
use Modules\Sirsoft\Ecommerce\Models\Cart;
use Modules\Sirsoft\Ecommerce\Services\CartService;
use PHPUnit\Framework\Attributes\DataProvider;

class TravelWorkflowTest extends WorkflowTestCase
{
    private const API = '/api/modules/raonslab-travel_lab';

    private function cart(User $user, Departure $departure, int $quantity = 1): int
    {
        app(TravelCartService::class)->add($user->id, $departure->id, $quantity);

        return (int) Cart::where('user_id', $user->id)->where('product_option_id', $departure->product_option_id)->sole()->id;
    }

    private function bearer(User $user): array
    {
        // 실제 요청 사이에는 새 guard가 토큰을 해석한다. 테스트의 장수 앱도 동일하게 만든다.
        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('travel-workflow-test')->plainTextToken];
    }

    private function assertRejected(callable $operation, int $status): void
    {
        try {
            $operation();
            $this->fail('거절되어야 할 작업이 성공했습니다.');
        } catch (HttpResponseException $e) {
            $this->assertSame($status, $e->getResponse()->getStatusCode());
        }
    }

    /**
     * @scenario flow=cart_authority
     *
     * @effects commerce_cart_written, quantity_updated, authoritative_amounts_returned, ordinary_cart_preserved
     */
    public function test_cart_uses_commerce_quantity_and_price_and_excludes_ordinary_items(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $ordinary = $this->ordinaryOption();
        app(CartService::class)->bulkAddToCart([
            'user_id' => $user->id, 'product_id' => $ordinary->product_id,
            'items' => [['product_option_id' => $ordinary->id, 'quantity' => 3]],
        ]);

        $cartId = $this->cart($user, $departure, 2);
        $cart = app(TravelCartService::class)->get($user->id);
        $this->assertCount(1, $cart['items']);
        $this->assertEquals(24000, $cart['totals']['subtotal']);
        $this->assertEquals(12000, $cart['items'][0]['unit_price']);
        $this->assertSame('KRW', $cart['currency_code']);

        $updated = app(TravelCartService::class)->update($user->id, $cartId, 4);
        $this->assertEquals(48000, $updated['totals']['subtotal']);
        $this->assertSame(4, Cart::findOrFail($cartId)->quantity);
        app(TravelCartService::class)->remove($user->id, $cartId);
        $this->assertDatabaseMissing('ecommerce_carts', ['id' => $cartId]);
        $this->assertDatabaseHas('ecommerce_carts', ['user_id' => $user->id, 'product_option_id' => $ordinary->id, 'quantity' => 3]);
    }

    /**
     * @scenario flow=price_change
     *
     * @effects current_price_snapshotted, capacity_reserved, selected_cart_removed, immutable_snapshot
     */
    public function test_inquiry_recalculates_current_price_and_preserves_snapshot_after_product_changes(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cartId = $this->cart($user, $departure, 2);
        $departure->product->update(['selling_price' => 17000]);
        $inquiry = app(InquiryService::class)->submit($user->id, [$cartId], ['name' => '테스터'], 'price-change');

        $this->assertEquals(34000, $inquiry->total_amount);
        $this->assertEquals(17000, $inquiry->items->sole()->unit_price);
        $this->assertSame(2, $inquiry->items->sole()->quantity);
        $this->assertSame(2, $departure->fresh()->reserved);
        $this->assertDatabaseMissing('ecommerce_carts', ['id' => $cartId]);

        $departure->product->update(['name' => ['ko' => '수정된 이름'], 'selling_price' => 1]);
        $departure->option->update(['selling_price' => 1]);
        $snapshot = $inquiry->fresh()->items->sole();
        $this->assertSame('테스트 여행', $snapshot->product_name['ko']);
        $this->assertEquals(34000, $snapshot->line_total);
        $this->assertDatabaseCount('ecommerce_orders', 0);
        $this->assertDatabaseCount('ecommerce_order_payments', 0);
    }

    /**
     * @scenario flow=idempotency
     *
     * @effects replay_returns_original, capacity_reserved_once, changed_payload_conflict, replay_after_cancel_safe
     */
    public function test_same_payload_replay_and_conflict_after_cart_removal_and_cancellation(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cartId = $this->cart($user, $departure, 2);
        $service = app(InquiryService::class);
        $inquiry = $service->submit($user->id, [$cartId], ['name' => '테스터', 'phone' => '01012345678'], 'stable-key');
        $replayed = $service->submit($user->id, [$cartId], ['phone' => '01012345678', 'name' => '테스터'], 'stable-key');
        $this->assertSame($inquiry->id, $replayed->id);
        $this->assertSame(2, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 1);
        $this->assertRejected(fn () => $service->submit($user->id, [$cartId], ['name' => '다른 사람'], 'stable-key'), 409);
        $this->assertDatabaseCount('travel_lab_inquiries', 1);
        $service->cancel($user->id, $inquiry->id);
        $service->submit($user->id, [$cartId], ['name' => '테스터', 'phone' => '01012345678'], 'stable-key');
        $this->assertSame(0, $departure->fresh()->reserved);
    }

    /**
     * @scenario flow=capacity_rollback
     *
     * @effects insufficient_capacity_rejected, all_reserves_rolled_back, selected_carts_preserved
     */
    public function test_capacity_failure_rolls_back_every_departure_and_keeps_carts(): void
    {
        $user = $this->createUser();
        $first = $this->departure(capacity: 5);
        $second = $this->departure(capacity: 5);
        $ids = [$this->cart($user, $first, 2), $this->cart($user, $second, 2)];
        $second->update(['reserved' => 4]);
        $this->assertRejected(fn () => app(InquiryService::class)->submit($user->id, array_reverse($ids), ['name' => '테스터'], 'rollback'), 409);
        $this->assertSame(0, $first->fresh()->reserved);
        $this->assertSame(4, $second->fresh()->reserved);
        $this->assertSame(2, Cart::whereIn('id', $ids)->count());
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertDatabaseCount('travel_lab_inquiry_items', 0);
    }

    /**
     * @scenario flow=capacity_boundary
     *
     * @effects exact_capacity_reserved, second_user_rejected, second_cart_preserved
     */
    public function test_exact_capacity_is_reserved_and_next_user_cannot_oversell(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $departure = $this->departure(capacity: 2);
        $firstCart = $this->cart($owner, $departure, 2);
        $secondCart = $this->cart($other, $departure, 1);
        app(InquiryService::class)->submit($owner->id, [$firstCart], ['name' => '첫 사용자'], 'first');
        $this->assertRejected(fn () => app(InquiryService::class)->submit($other->id, [$secondCart], ['name' => '둘째 사용자'], 'second'), 409);
        $this->assertSame(2, $departure->fresh()->reserved);
        $this->assertDatabaseHas('ecommerce_carts', ['id' => $secondCart]);
        $this->assertDatabaseCount('travel_lab_inquiries', 1);
    }

    /**
     * @scenario flow=scope
     *
     * @effects foreign_cart_rejected, ordinary_cart_rejected, foreign_inquiry_rejected, own_list_scoped
     */
    public function test_ownership_and_travel_scope_are_enforced_inside_services(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $departure = $this->departure();
        $cartId = $this->cart($owner, $departure);
        $this->assertSame([], app(TravelCartService::class)->get($other->id)['items']);
        $this->assertRejected(fn () => app(TravelCartService::class)->update($other->id, $cartId, 2), 404);
        $this->assertRejected(fn () => app(TravelCartService::class)->remove($other->id, $cartId), 404);
        $this->assertRejected(fn () => app(InquiryService::class)->submit($other->id, [$cartId], ['name' => '테스터'], 'foreign-cart'), 409);

        $ordinary = $this->ordinaryOption();
        app(CartService::class)->bulkAddToCart(['user_id' => $owner->id, 'product_id' => $ordinary->product_id, 'items' => [['product_option_id' => $ordinary->id, 'quantity' => 1]]]);
        $ordinaryCartId = (int) Cart::where('product_option_id', $ordinary->id)->sole()->id;
        $this->assertRejected(fn () => app(TravelCartService::class)->remove($owner->id, $ordinaryCartId), 404);
        $this->assertRejected(fn () => app(InquiryService::class)->submit($owner->id, [$ordinaryCartId], ['name' => '테스터'], 'ordinary-cart'), 409);
        $inquiry = app(InquiryService::class)->submit($owner->id, [$cartId], ['name' => '테스터'], 'owner');
        $this->assertRejected(fn () => app(InquiryService::class)->findOwn($other->id, $inquiry->id), 404);
        $this->assertRejected(fn () => app(InquiryService::class)->cancel($other->id, $inquiry->id), 404);
        $this->assertCount(0, app(InquiryService::class)->listOwn($other->id)->items());
        $this->assertCount(1, app(InquiryService::class)->listOwn($owner->id)->items());
    }

    public static function unavailableDepartures(): array
    {
        return array_map(static fn (string $condition): array => [$condition], [
            'unpublished', 'inactive_departure', 'inactive_option', 'past_date', 'invalid_return_date', 'suspended_product', 'option_product_mismatch',
        ]);
    }

    /**
     * @scenario flow=eligibility
     *
     * @effects unavailable_departure_rejected, unavailable_submit_rolled_back
     */
    #[DataProvider('unavailableDepartures')]
    public function test_departure_eligibility_is_rechecked_after_cart_add(string $condition): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cartId = $this->cart($user, $departure);
        match ($condition) {
            'unpublished' => TravelProduct::where('product_id', $departure->product_id)->update(['published' => false]),
            'inactive_departure' => $departure->update(['is_active' => false]),
            'inactive_option' => $departure->option->update(['is_active' => false]),
            'past_date' => $departure->update(['departure_date' => now()->subDay()->toDateString()]),
            'invalid_return_date' => $departure->update(['return_date' => now()->toDateString()]),
            'suspended_product' => $departure->product->update(['sales_status' => 'suspended']),
            'option_product_mismatch' => $departure->option->update(['product_id' => $this->ordinaryOption()->product_id]),
        };
        $this->assertRejected(fn () => app(TravelCartService::class)->add($user->id, $departure->id, 1), 409);
        $this->assertRejected(fn () => app(InquiryService::class)->submit($user->id, [$cartId], ['name' => '테스터'], 'unavailable'), 409);
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseHas('ecommerce_carts', ['id' => $cartId, 'quantity' => 1]);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
    }

    public static function transitions(): array
    {
        $cases = ['TEST_INQUIRY', 'UNDER_REVIEW', 'TEST_ACCEPTED', 'DECLINED', 'CANCELLED'];
        $data = [];
        foreach ($cases as $from) {
            foreach ($cases as $to) {
                $data[$from.'-'.$to] = [$from, $to];
            }
        }

        return $data;
    }

    /**
     * @scenario flow=transition
     *
     * @effects transition_matrix_enforced, capacity_released_once, invalid_transition_rolled_back, admin_note_written
     */
    #[DataProvider('transitions')]
    public function test_complete_transition_matrix_and_capacity_release(string $fromName, string $toName): void
    {
        $user = $this->createUser();
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.read', 'raonslab-travel_lab.inquiries.update']);
        $departure = $this->departure();
        $inquiry = app(InquiryService::class)->submit($user->id, [$this->cart($user, $departure, 2)], ['name' => '테스터'], 'transition');
        $from = constant(InquiryStatus::class.'::'.$fromName);
        $to = constant(InquiryStatus::class.'::'.$toName);
        $inquiry->update(['status' => $from]);
        $releasedBefore = in_array($fromName, ['DECLINED', 'CANCELLED'], true);
        if ($releasedBefore) {
            $departure->refresh()->update(['reserved' => 0]);
        }
        $allowed = [
            'TEST_INQUIRY' => ['UNDER_REVIEW', 'DECLINED', 'CANCELLED'],
            'UNDER_REVIEW' => ['TEST_ACCEPTED', 'DECLINED', 'CANCELLED'],
            'TEST_ACCEPTED' => ['CANCELLED'],
            'DECLINED' => [], 'CANCELLED' => [],
        ];
        if ($fromName === $toName || in_array($toName, $allowed[$fromName], true)) {
            $updated = app(InquiryService::class)->transition($admin->id, $inquiry->id, $to, '관리자 검토');
            $this->assertSame($to, $updated->status);
            $expectedReserve = $releasedBefore || in_array($toName, ['DECLINED', 'CANCELLED'], true) ? 0 : 2;
            $this->assertSame($expectedReserve, $departure->fresh()->reserved);
            app(InquiryService::class)->transition($admin->id, $inquiry->id, $to, '두 번째 메모');
            $this->assertSame($expectedReserve, $departure->fresh()->reserved);
            $this->assertSame($fromName === $toName ? null : '관리자 검토', $inquiry->fresh()->admin_note);
        } else {
            $this->assertRejected(fn () => app(InquiryService::class)->transition($admin->id, $inquiry->id, $to, '허용되지 않음'), 409);
            $this->assertSame($from, $inquiry->fresh()->status);
            $this->assertNull($inquiry->fresh()->admin_note);
            $this->assertSame($releasedBefore ? 0 : 2, $departure->fresh()->reserved);
        }
    }

    /**
     * @scenario flow=http_auth
     *
     * @effects unauthenticated_rejected, bearer_authenticated, admin_permission_enforced
     */
    public function test_real_bearer_auth_and_admin_permissions_on_routes_and_services(): void
    {
        $this->getJson(self::API.'/cart')->assertUnauthorized();
        $this->postJson(self::API.'/inquiries', [])->assertUnauthorized();
        $user = $this->createUser();
        $headers = $this->bearer($user);
        $this->getJson(self::API.'/cart', $headers)->assertOk()->assertJsonPath('success', true);
        $this->getJson(self::API.'/admin/inquiries', $headers)->assertForbidden();
        $this->assertRejected(fn () => app(InquiryService::class)->listAdmin($user->id), 403);
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.read']);
        $this->getJson(self::API.'/admin/inquiries', $this->bearer($admin))->assertOk();
        $inquiry = app(InquiryService::class)->submit($user->id, [$this->cart($user, $this->departure())], ['name' => '테스터'], 'admin-auth');
        $this->patchJson(self::API.'/admin/inquiries/'.$inquiry->id, ['status' => InquiryStatus::UNDER_REVIEW->value], $this->bearer($admin))->assertForbidden();
        $this->assertRejected(fn () => app(InquiryService::class)->transition($admin->id, $inquiry->id, InquiryStatus::UNDER_REVIEW), 403);
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $inquiry->fresh()->status);
    }

    /**
     * @scenario flow=http_validation
     *
     * @effects supplied_money_rejected, invalid_quantity_rejected, idempotency_header_supported, supplied_payload_not_persisted
     */
    public function test_money_quantity_and_idempotency_key_http_validation(): void
    {
        $user = $this->createUser();
        $headers = $this->bearer($user);
        $departure = $this->departure();
        foreach (['unit_price', 'total_amount', 'currency_code', 'line_total', 'price', 'selling_price'] as $field) {
            $this->postJson(self::API.'/cart', ['departure_id' => $departure->id, 'quantity' => 1, $field => 1], $headers)->assertUnprocessable();
        }
        foreach ([0, -1, 1.5, 100] as $quantity) {
            $this->postJson(self::API.'/cart', ['departure_id' => $departure->id, 'quantity' => $quantity], $headers)->assertUnprocessable();
        }
        $this->assertDatabaseCount('ecommerce_carts', 0);
        $cartId = $this->cart($user, $departure);
        $payload = ['cart_ids' => [$cartId], 'contact' => ['name' => '테스터']];
        $this->postJson(self::API.'/inquiries', $payload, $headers)->assertUnprocessable();
        $this->postJson(self::API.'/inquiries', $payload + ['idempotency_key' => 'tamper-key', 'total_amount' => 1], $headers)->assertUnprocessable();
        $this->postJson(self::API.'/inquiries', $payload + ['idempotency_key' => 'quantity-key', 'quantity' => 9], $headers)->assertUnprocessable();
        $this->postJson(self::API.'/inquiries', ['cart_ids' => [$cartId, $cartId], 'contact' => ['name' => '테스터'], 'idempotency_key' => 'duplicate-key'], $headers)->assertUnprocessable();
        $this->postJson(self::API.'/inquiries', $payload + ['idempotency_key' => 'body-stable-key'], $headers + ['Idempotency-Key' => 'header-stable-key'])->assertUnprocessable();
        $created = $this->postJson(self::API.'/inquiries', $payload, $headers + ['Idempotency-Key' => 'header-key']);
        $this->assertContains($created->status(), [200, 201]);
        $this->assertDatabaseHas('travel_lab_inquiries', ['user_id' => $user->id, 'idempotency_key' => 'header-key', 'total_amount' => 12000]);
        $this->assertSame(1, $departure->fresh()->reserved);
    }

    /**
     * @scenario flow=write_rollback
     *
     * @effects inquiry_insert_failure_rolled_back, all_reserves_rolled_back, selected_carts_preserved
     */
    public function test_snapshot_insert_failure_rolls_back_existing_reserve_and_inquiry_writes(): void
    {
        $user = $this->createUser();
        $first = $this->departure();
        $second = $this->departure();
        $ids = [$this->cart($user, $first, 2), $this->cart($user, $second, 3)];
        DB::unprepared("CREATE TRIGGER travel_snapshot_failure BEFORE INSERT ON travel_lab_inquiry_items BEGIN SELECT RAISE(ABORT, 'travel_snapshot_test_failure'); END");
        try {
            app(InquiryService::class)->submit($user->id, $ids, ['name' => '테스터'], 'write-rollback');
            $this->fail('DB 오류를 발생시킨 스냅샷 쓰기가 성공했습니다.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('travel_snapshot_test_failure', $e->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER travel_snapshot_failure');
        }
        $this->assertSame(0, $first->fresh()->reserved);
        $this->assertSame(0, $second->fresh()->reserved);
        $this->assertSame(2, Cart::whereIn('id', $ids)->count());
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertDatabaseCount('travel_lab_inquiry_items', 0);
        // 동일 키는 실패로 소모되지 않으며 정상 재시도는 한 건만 만든다.
        app(InquiryService::class)->submit($user->id, $ids, ['name' => '테스터'], 'write-rollback');
        $this->assertDatabaseCount('travel_lab_inquiries', 1);
        $this->assertSame(2, $first->fresh()->reserved);
        $this->assertSame(3, $second->fresh()->reserved);
    }

    /**
     * @scenario flow=release_rollback
     *
     * @effects first_release_rolled_back, invalid_transition_rolled_back, admin_note_unchanged
     */
    public function test_second_departure_release_failure_rolls_back_first_release_and_transition(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.update']);
        $first = $this->departure();
        $second = $this->departure();
        $ids = [$this->cart($user, $first, 2), $this->cart($user, $second, 3)];
        $inquiry = app(InquiryService::class)->submit($user->id, $ids, ['name' => '테스터'], 'release-rollback');
        $inquiry->update(['admin_note' => '기존 메모']);
        $second->update(['reserved' => 1]);
        $this->assertRejected(fn () => app(InquiryService::class)->transition($admin->id, $inquiry->id, InquiryStatus::CANCELLED, '변경 메모'), 409);
        $this->assertSame(2, $first->fresh()->reserved);
        $this->assertSame(1, $second->fresh()->reserved);
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $inquiry->fresh()->status);
        $this->assertSame('기존 메모', $inquiry->fresh()->admin_note);
    }

    /**
     * @scenario flow=admin_scope
     *
     * @effects admin_list_self_scoped, admin_detail_scope_enforced, admin_update_scope_enforced
     */
    public function test_self_scoped_admin_cannot_list_read_or_update_other_users_inquiries(): void
    {
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.read', 'raonslab-travel_lab.inquiries.update']);
        $role = $admin->roles()->firstOrFail();
        foreach (['read', 'update'] as $operation) {
            $permission = Permission::where('identifier', 'raonslab-travel_lab.inquiries.'.$operation)->sole();
            $permission->update(['owner_key' => 'user_id', 'resource_route_key' => 'inquiry']);
            $role->permissions()->updateExistingPivot($permission->id, ['scope_type' => 'self']);
        }
        PermissionHelper::clearPermissionScopeCache();
        $user = $this->createUser();
        $own = app(InquiryService::class)->submit($admin->id, [$this->cart($admin, $this->departure())], ['name' => '관리자'], 'self');
        $foreign = app(InquiryService::class)->submit($user->id, [$this->cart($user, $this->departure())], ['name' => '다른 사용자'], 'foreign');
        $service = app(InquiryService::class);
        $this->assertSame([$own->id], array_map(static fn ($item) => $item->id, $service->listAdmin($admin->id)->items()));
        $this->assertSame($own->id, $service->findAdmin($admin->id, $own->id)->id);
        $this->assertRejected(fn () => $service->findAdmin($admin->id, $foreign->id), 403);
        $this->assertRejected(fn () => $service->transition($admin->id, $foreign->id, InquiryStatus::UNDER_REVIEW), 403);
        $this->getJson(self::API.'/admin/inquiries/'.$foreign->id, $this->bearer($admin))->assertForbidden();
        $this->patchJson(self::API.'/admin/inquiries/'.$foreign->id, ['status' => InquiryStatus::UNDER_REVIEW->value], $this->bearer($admin))->assertForbidden();
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $foreign->fresh()->status);
        $this->assertSame(InquiryStatus::UNDER_REVIEW, $service->transition($admin->id, $own->id, InquiryStatus::UNDER_REVIEW)->status);
    }

    /**
     * @scenario flow=resource
     *
     * @effects unloaded_resource_safe, loaded_snapshot_serialized
     */
    public function test_resource_handles_unloaded_items_and_serializes_loaded_immutable_snapshot(): void
    {
        $user = $this->createUser();
        $inquiry = app(InquiryService::class)->submit($user->id, [$this->cart($user, $this->departure())], ['name' => '테스터'], 'resource');
        $inquiry->unsetRelation('items');
        $resolved = (new InquiryResource($inquiry))->resolve(request());
        $this->assertSame($inquiry->id, $resolved['id']);
        $this->assertArrayNotHasKey('items', $resolved);
        $inquiry->load('items');
        $resolved = (new InquiryResource($inquiry))->resolve(request());
        $this->assertCount(1, $resolved['items']);
        $this->assertEquals(12000, $resolved['items'][0]['line_total']);
        $this->assertSame(1, $resolved['items'][0]['quantity']);
    }

    /**
     * @scenario flow=stale_cleanup
     *
     * @effects stale_rows_visible, stale_amounts_excluded, stale_rows_individually_removed, valid_row_updated
     */
    public function test_unavailable_rows_can_be_cleaned_up_and_do_not_block_other_cart_updates(): void
    {
        $user = $this->createUser();
        $first = $this->departure();
        $second = $this->departure();
        $valid = $this->departure();
        $firstCart = $this->cart($user, $first);
        $secondCart = $this->cart($user, $second);
        $validCart = $this->cart($user, $valid);
        $first->update(['is_active' => false]);
        $second->update(['departure_date' => now()->subDay()->toDateString()]);
        $service = app(TravelCartService::class);
        $cart = $service->get($user->id);
        $this->assertCount(3, $cart['items']);
        $byId = collect($cart['items'])->keyBy('id');
        foreach ([$firstCart, $secondCart] as $id) {
            $this->assertFalse($byId[$id]['available']);
            $this->assertNotEmpty($byId[$id]['unavailable_reason']);
            $this->assertNull($byId[$id]['unit_price']);
            $this->assertNull($byId[$id]['line_total']);
        }
        $this->assertEquals(12000, $cart['totals']['subtotal']);
        $cart = $service->update($user->id, $validCart, 2);
        $this->assertEquals(24000, $cart['totals']['subtotal']);
        $this->assertCount(2, $service->remove($user->id, $firstCart)['items']);
        $this->assertCount(1, $service->remove($user->id, $secondCart)['items']);
        $this->assertDatabaseHas('ecommerce_carts', ['id' => $validCart, 'quantity' => 2]);
    }

    /**
     * @scenario flow=quantity_change
     *
     * @effects current_quantity_snapshotted, capacity_matches_quantity, authoritative_amounts_returned
     */
    public function test_inquiry_uses_quantity_updated_through_the_real_commerce_service(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cartId = $this->cart($user, $departure);
        app(CartService::class)->updateQuantity($cartId, 3, $user->id, null);
        $inquiry = app(InquiryService::class)->submit($user->id, [$cartId], ['name' => '테스터'], 'quantity-change');
        $this->assertSame(3, $inquiry->items->sole()->quantity);
        $this->assertEquals(36000, $inquiry->total_amount);
        $this->assertEquals(36000, $inquiry->items->sole()->line_total);
        $this->assertSame(3, $departure->fresh()->reserved);
        $this->assertDatabaseMissing('ecommerce_carts', ['id' => $cartId]);
    }

    public static function unsupportedCartShapes(): array
    {
        return [['duplicate_option'], ['additional_options']];
    }

    /**
     * @scenario flow=unsupported_cart
     *
     * @effects unsupported_cart_rejected, unsupported_cart_visible, unsupported_amounts_excluded, selected_carts_preserved
     */
    #[DataProvider('unsupportedCartShapes')]
    public function test_duplicate_option_lines_and_additional_selections_cannot_create_travel_inquiries(string $shape): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cartId = $this->cart($user, $departure);
        $ids = [$cartId];
        // 별도 커머스 진입점 또는 비정상 저장값을 재현하는 테스트 전용 DB fixture.
        if ($shape === 'duplicate_option') {
            $duplicate = Cart::create([
                'user_id' => $user->id, 'product_id' => $departure->product_id,
                'product_option_id' => $departure->product_option_id, 'quantity' => 2,
            ]);
            $ids[] = $duplicate->id;
        } else {
            Cart::findOrFail($cartId)->update(['additional_option_selections' => [['additional_option_id' => 123, 'value_id' => 456]]]);
        }
        $this->assertRejected(fn () => app(InquiryService::class)->submit($user->id, $ids, ['name' => '테스터'], 'unsupported'), 409);
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertSame(count($ids), Cart::whereIn('id', $ids)->count());
        $cart = app(TravelCartService::class)->get($user->id);
        $this->assertCount(count($ids), $cart['items']);
        foreach ($cart['items'] as $item) {
            $this->assertFalse($item['available']);
            $this->assertSame('unsupported_cart', $item['unavailable_reason']);
            $this->assertNull($item['unit_price']);
            $this->assertNull($item['line_total']);
        }
        $this->assertEquals(0, $cart['totals']['subtotal']);
    }
}
