<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Raonslab\TravelLab\Tests\WorkflowTestCase;
use Modules\Sirsoft\Ecommerce\Models\Cart;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Services\CartService;
use Modules\Sirsoft\Ecommerce\Services\ProductImageService;
use Modules\Sirsoft\Ecommerce\Services\ProductService;

/**
 * W03 독립 보안 검토(비구현자) 회귀·재현 테스트.
 *
 * `test_finding_*` 는 검토 시점(28ada286)의 결함을 **재현**한다. 수정되면 실패하므로
 * 수정자는 그 단언을 기대 동작으로 뒤집어야 한다. 나머지는 통과해야 하는 보안 불변식이다.
 * SQLite 증거이며 MySQL 행 잠금·실제 동시성 검증이 아니다.
 */
class W03SecurityReviewTest extends WorkflowTestCase
{
    private const API = '/api/modules/raonslab-travel_lab';

    private function bearer(User $user): array
    {
        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('w03-security')->plainTextToken];
    }

    private function cartId(User $user, Departure $departure, int $quantity = 1): int
    {
        app(TravelCartService::class)->add($user->id, $departure->id, $quantity);

        return (int) Cart::where('user_id', $user->id)->where('product_option_id', $departure->product_option_id)->sole()->id;
    }

    private function httpStatusOf(callable $operation): int
    {
        try {
            $operation();
        } catch (HttpResponseException $e) {
            return $e->getResponse()->getStatusCode();
        }

        return 200;
    }

    /** W03-01: 출발 일정이 있는 여행 상품을 native 상품 삭제가 도메인 사유 없이 FK 오류로만 막고, 그 전에 이미지 디렉토리를 지운다. */
    public function test_finding_native_product_delete_of_travel_product_fails_on_fk_after_irreversible_image_cleanup(): void
    {
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $storage = (fn () => $this->storage)->call(app(ProductImageService::class));
        $path = "products/{$product->product_code}/w03-probe.txt";
        $storage->put('images', $path, 'probe');
        $this->assertTrue($storage->exists('images', $path));
        $this->actingAs($this->createAdminUser(['sirsoft-ecommerce.products.delete']));

        try {
            app(ProductService::class)->delete($product);
            $this->fail('Travel product with departures was deleted.');
        } catch (QueryException $e) {
            // 결함: 도메인 예외(409 등)가 아니라 FK 위반 → generic 500 경로.
            $this->assertStringContainsStringIgnoringCase('foreign key', $e->getMessage());
        }
        // DB 무결성은 FK가 지켰다.
        $this->assertNotNull(Product::find($product->id));
        $this->assertNotNull(Departure::find($departure->id));
        // 결함: 트랜잭션 밖 저장소 정리는 롤백되지 않는다 — 남은 상품의 이미지가 소실된다.
        $this->assertFalse($storage->exists('images', $path));
        $storage->deleteDirectory('images', "products/{$product->product_code}");
    }

    /** W03-02: native 옵션 동기화가 출발 일정에 연결된 옵션 제거를 도메인 사유 없이 FK 오류(500)로만 막는다. */
    public function test_finding_native_option_sync_removing_departure_option_is_rejected_only_by_fk(): void
    {
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $sync = (new \ReflectionClass(ProductService::class))->getMethod('syncOptions');

        try {
            // 기존 옵션을 빼고 새 옵션만 보내면 native 서비스는 기존 옵션을 hard delete 한다.
            $sync->invoke(app(ProductService::class), $product, [[
                'option_code' => 'W03-NEW', 'option_values' => [], 'option_name' => ['ko' => '신규', 'en' => 'New'],
                'price_adjustment' => 0, 'stock_quantity' => 10, 'is_active' => true,
            ]]);
            $this->fail('Departure option was removed.');
        } catch (QueryException $e) {
            // 결함: OptionHasOrderHistoryException 같은 도메인 거절이 아니다.
            $this->assertStringContainsStringIgnoringCase('foreign key', $e->getMessage());
        }
        $this->assertNotNull($departure->option->fresh());
    }

    /** W03-03: '오늘' 판정이 app.timezone(UTC) 기준이라 KST 출발 당일 00:00–08:59 에는 당일 출발을 담고 문의할 수 있다. */
    public function test_finding_same_day_cutoff_uses_utc_not_kst(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $departure = $this->departure();
        // 2026-12-01 00:30 KST == 2026-11-30 15:30 UTC. 출발일은 KST 기준 "오늘".
        $departure->forceFill(['departure_date' => '2026-12-01', 'return_date' => '2026-12-02'])->save();
        $this->travelTo(new \DateTimeImmutable('2026-11-30 15:30:00', new \DateTimeZone('UTC')));
        $user = $this->createUser();
        $cart = $this->cartId($user, $departure);
        $inquiry = app(InquiryService::class)->submit($user->id, [$cart], ['name' => 'W03'], 'w03-kst-same-day');
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $inquiry->status);
        $this->travelBack();
    }

    /** native 카트 API로 정원 초과 수량을 만들어도 제출 시 잠금 재검증이 거절하고 예약·카트를 바꾸지 않는다. */
    public function test_native_cart_quantity_bypass_is_rechecked_at_submit(): void
    {
        $departure = $this->departure(capacity: 3);
        $user = $this->createUser();
        $cart = $this->cartId($user, $departure);
        app(CartService::class)->updateQuantity($cart, 50, $user->id, null);
        $this->assertSame(409, $this->httpStatusOf(fn () => app(InquiryService::class)->submit($user->id, [$cart], ['name' => 'W03'], 'w03-native-bypass')));
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertSame(50, (int) Cart::findOrFail($cart)->quantity);
    }

    /** 클라이언트 금액·상태·소유자 필드는 HTTP 경계에서 거절되고 아무 것도 쓰지 않는다. */
    public function test_client_money_owner_and_status_fields_are_rejected_without_writes(): void
    {
        $departure = $this->departure();
        $user = $this->createUser();
        $this->postJson(self::API.'/cart', ['departure_id' => $departure->id, 'quantity' => 1, 'unit_price' => 1], $this->bearer($user))->assertStatus(422);
        $this->postJson(self::API.'/cart', ['departure_id' => $departure->id, 'quantity' => 1, 'user_id' => 999], $this->bearer($user))->assertStatus(422);
        $this->assertDatabaseCount('ecommerce_carts', 0);
        $cart = $this->cartId($user, $departure);
        foreach ([['total_amount' => 1], ['status' => 'TEST_ACCEPTED'], ['user_id' => 999], ['currency_code' => 'USD']] as $extra) {
            $this->postJson(self::API.'/inquiries', ['cart_ids' => [$cart], 'contact' => ['name' => 'W03'], 'idempotency_key' => 'w03-tamper-0001', ...$extra], $this->bearer($user))->assertStatus(422);
        }
        $this->postJson(self::API.'/inquiries', ['cart_ids' => [$cart], 'contact' => ['name' => 'W03', 'email' => 'x@example.invalid'], 'idempotency_key' => 'w03-tamper-0002'], $this->bearer($user))->assertStatus(422);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertSame(0, $departure->fresh()->reserved);
    }

    /** 다른 회원 카트 ID로 제출·수정·삭제할 수 없고, 존재 여부를 404로만 노출한다. */
    public function test_foreign_cart_ids_cannot_be_submitted_patched_or_deleted(): void
    {
        $departure = $this->departure();
        $owner = $this->createUser();
        $intruder = $this->createUser();
        $cart = $this->cartId($owner, $departure, 2);
        $this->postJson(self::API.'/inquiries', ['cart_ids' => [$cart], 'contact' => ['name' => 'X'], 'idempotency_key' => 'w03-foreign-01'], $this->bearer($intruder))->assertStatus(409);
        $this->patchJson(self::API."/cart/{$cart}", ['quantity' => 1], $this->bearer($intruder))->assertStatus(404);
        $this->deleteJson(self::API."/cart/{$cart}", [], $this->bearer($intruder))->assertStatus(404);
        $this->assertSame(2, (int) Cart::findOrFail($cart)->quantity);
        $this->assertSame(0, $departure->fresh()->reserved);
    }

    /** 다른 회원 문의 열람·취소는 404이며 상태·정원이 변하지 않는다. 관리자 PATCH 는 금액 필드를 받지 않는다. */
    public function test_foreign_inquiry_read_cancel_and_admin_money_patch_are_rejected(): void
    {
        $departure = $this->departure();
        $owner = $this->createUser();
        $intruder = $this->createUser();
        $inquiry = app(InquiryService::class)->submit($owner->id, [$this->cartId($owner, $departure, 2)], ['name' => 'Owner'], 'w03-owner-0001');
        $this->getJson(self::API."/inquiries/{$inquiry->id}", $this->bearer($intruder))->assertStatus(404);
        $this->postJson(self::API."/inquiries/{$inquiry->id}/cancel", [], $this->bearer($intruder))->assertStatus(404);
        $this->getJson(self::API.'/admin/inquiries', $this->bearer($intruder))->assertStatus(403);
        $this->patchJson(self::API."/admin/inquiries/{$inquiry->id}", ['status' => 'DECLINED'], $this->bearer($intruder))->assertStatus(403);
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.read', 'raonslab-travel_lab.inquiries.update']);
        $this->patchJson(self::API."/admin/inquiries/{$inquiry->id}", ['status' => 'UNDER_REVIEW', 'total_amount' => 1], $this->bearer($admin))->assertStatus(422);
        $fresh = $inquiry->fresh();
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $fresh->status);
        $this->assertSame('24000.00', (string) $fresh->total_amount);
        $this->assertSame(2, $departure->fresh()->reserved);
    }

    /** 소유자 취소 후 관리자 거절·재취소는 정원을 두 번 풀지 않고, 같은 키 재전송은 취소된 원본을 돌려준다. */
    public function test_cancel_then_admin_decline_and_replay_release_exactly_once(): void
    {
        $departure = $this->departure(capacity: 2);
        $owner = $this->createUser();
        $cart = $this->cartId($owner, $departure, 2);
        $inquiry = app(InquiryService::class)->submit($owner->id, [$cart], ['name' => 'Owner'], 'w03-release-0001');
        $this->assertSame(2, $departure->fresh()->reserved);
        app(InquiryService::class)->cancel($owner->id, $inquiry->id);
        app(InquiryService::class)->cancel($owner->id, $inquiry->id);
        $admin = $this->createAdminUser(['raonslab-travel_lab.inquiries.read', 'raonslab-travel_lab.inquiries.update']);
        $this->assertSame(409, $this->httpStatusOf(fn () => app(InquiryService::class)->transition($admin->id, $inquiry->id, InquiryStatus::DECLINED)));
        $this->assertSame(0, $departure->fresh()->reserved);
        $replay = app(InquiryService::class)->submit($owner->id, [$cart], ['name' => 'Owner'], 'w03-release-0001');
        $this->assertSame($inquiry->id, $replay->id);
        $this->assertSame(InquiryStatus::CANCELLED, $replay->status);
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertSame(2, $inquiry->events()->count());
    }

    /** 관리자 정원 축소는 예약 아래로 내려갈 수 없고, 옵션 재고보다 큰 정원 상향도 거절된다. */
    public function test_admin_capacity_cannot_go_below_reserved_or_above_stock(): void
    {
        $departure = $this->departure(capacity: 5);
        $owner = $this->createUser();
        app(InquiryService::class)->submit($owner->id, [$this->cartId($owner, $departure, 3)], ['name' => 'Owner'], 'w03-capacity-01');
        Route::prefix('api/modules/raonslab-travel_lab')->name('api.modules.raonslab-travel_lab.')
            ->middleware('api')->group(dirname(__DIR__, 2).'/src/routes/catalog.php');
        Route::getRoutes()->refreshNameLookups();
        $admin = $this->createAdminUser(['raonslab-travel_lab.catalog.read', 'raonslab-travel_lab.catalog.update']);
        $body = fn (int $capacity) => [
            'product_option_id' => $departure->product_option_id, 'capacity' => $capacity,
            'departure_date' => $departure->departure_date->toDateString(), 'return_date' => $departure->return_date->toDateString(),
        ];
        $url = self::API."/admin/catalog/{$departure->product_id}/departures/{$departure->id}";
        $this->putJson($url, $body(2), $this->bearer($admin))->assertStatus(409);
        $this->putJson($url, $body(1001), $this->bearer($admin))->assertStatus(409);
        $this->putJson($url, [...$body(4), 'reserved' => 0], $this->bearer($admin))->assertStatus(422);
        $this->putJson($url, $body(3), $this->bearer($admin))->assertOk();
        $fresh = $departure->fresh();
        $this->assertSame(3, $fresh->capacity);
        $this->assertSame(3, $fresh->reserved);
        $member = $this->createUser();
        $this->putJson($url, $body(4), $this->bearer($member))->assertStatus(403);
    }
}
