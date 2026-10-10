<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Extension\HookListenerRegistrar;
use App\Extension\HookManager;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelCatalogConflictResponse;
use Modules\Raonslab\TravelLab\Listeners\ProtectTravelCommerceCatalog;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Raonslab\TravelLab\Support\TravelDate;
use Modules\Raonslab\TravelLab\Tests\WorkflowTestCase;
use Modules\Sirsoft\Ecommerce\Exceptions\ProductHasOrderHistoryException;
use Modules\Sirsoft\Ecommerce\Http\Controllers\Admin\ProductController;
use Modules\Sirsoft\Ecommerce\Models\Cart;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Services\CartService;
use Modules\Sirsoft\Ecommerce\Services\ProductImageService;
use Modules\Sirsoft\Ecommerce\Services\ProductInquiryService;
use Modules\Sirsoft\Ecommerce\Services\ProductService;

/**
 * W03 독립 보안 검토(비구현자) 회귀·재현 테스트.
 *
 * 검토 시점(28ada286)의 삭제/옵션/시간대 결함은 기대 동작으로 뒤집어 회귀를 검사한다.
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

    protected function setUp(): void
    {
        parent::setUp();
        HookListenerRegistrar::register(ProtectTravelCommerceCatalog::class, 'raonslab-travel_lab');
    }

    /** W03-01: synchronous guard runs before image and native Q&A cleanup. */
    public function test_native_travel_delete_rejects_before_image_and_inquiry_cleanup(): void
    {
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $storage = (fn () => $this->storage)->call(app(ProductImageService::class));
        $path = "products/{$product->product_code}/w03-probe.txt";
        $storage->put('images', $path, 'probe');
        $this->assertTrue($storage->exists('images', $path));
        $inquiries = \Mockery::mock(ProductInquiryService::class);
        $inquiries->shouldNotReceive('deleteInquiriesForProduct');
        $this->app->instance(ProductInquiryService::class, $inquiries);
        $this->actingAs($this->createAdminUser(['sirsoft-ecommerce.products.delete']));
        try {
            app(ProductService::class)->delete($product);
            $this->fail('Travel product was deleted.');
        } catch (ProductHasOrderHistoryException $e) {
            $this->assertSame(__('raonslab-travel_lab::workflow.product_delete_restricted'), $e->getMessage());
        }
        $this->assertNotNull(Product::find($product->id));
        $this->assertNotNull(Departure::find($departure->id));
        $this->assertTrue($storage->exists('images', $path));
        $storage->deleteDirectory('images', "products/{$product->product_code}");
    }

    public function test_native_delete_guard_still_protects_departures_if_travel_metadata_is_missing(): void
    {
        $departure = $this->departure();
        TravelProduct::where('product_id', $departure->product_id)->delete();
        $this->actingAs($this->createAdminUser(['sirsoft-ecommerce.products.delete']));
        $this->expectException(ProductHasOrderHistoryException::class);
        app(ProductService::class)->delete(Product::findOrFail($departure->product_id));
    }

    /** W03-01 HTTP native controller + module adapter exposes a truthful 409. */
    public function test_native_delete_http_returns_travel_reason_and_ordinary_delete_stays_native(): void
    {
        Route::delete('/api/w03-native-products/{product}', [ProductController::class, 'destroy'])
            ->middleware(['api', 'auth:sanctum', TravelCatalogConflictResponse::class])->name('w03.native-delete');
        $admin = $this->createAdminUser(['sirsoft-ecommerce.products.delete']);
        $departure = $this->departure();
        $this->deleteJson('/api/w03-native-products/'.$departure->product_id, [], $this->bearer($admin))
            ->assertStatus(409)->assertJsonPath('message', __('raonslab-travel_lab::workflow.product_delete_restricted'));
        $option = $this->ordinaryOption();
        $this->deleteJson('/api/w03-native-products/'.$option->product_id, [], $this->bearer($admin))
            ->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertDatabaseMissing('ecommerce_products', ['id' => $option->product_id]);
        $this->assertNotNull(Departure::find($departure->id));
    }

    /** W03-02: validate complete option payload before updates/inserts/deletion. */
    public function test_native_option_sync_removal_rejects_before_any_product_or_option_write(): void
    {
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $originalName = $product->name;
        $optionCount = $product->options()->count();
        $this->actingAs($this->createAdminUser(['sirsoft-ecommerce.products.update']));
        try {
            app(ProductService::class)->update($product, ['name' => ['ko' => 'must not save'], 'options' => [[
                'option_code' => 'W03-NEW', 'option_values' => [], 'option_name' => ['ko' => '신규', 'en' => 'New'],
                'price_adjustment' => 0, 'stock_quantity' => 10, 'is_active' => true,
            ]]]);
            $this->fail('Departure option removed.');
        } catch (ValidationException $e) {
            $this->assertSame([__('raonslab-travel_lab::workflow.option_delete_restricted')], $e->errors()['options']);
        }
        $this->assertSame($originalName, $product->fresh()->name);
        $this->assertSame($optionCount, $product->options()->count());
        $this->assertNotNull($departure->option->fresh());
    }

    public function test_native_option_removal_http_has_field_reason_and_ordinary_options_are_unrestricted(): void
    {
        Route::put('/api/w03-native-products/{product}', [ProductController::class, 'update'])
            ->middleware(['api', 'auth:sanctum'])->name('w03.native-update');
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $admin = $this->createAdminUser(['sirsoft-ecommerce.products.update']);
        $newOption = ['option_code' => 'W03-HTTP-NEW', 'option_values' => [['key' => ['ko' => '옵션', 'en' => 'Option'], 'value' => ['ko' => '신규', 'en' => 'New']]],
            'option_name' => ['ko' => '신규', 'en' => 'New'], 'list_price' => 15000,
            'selling_price' => 14000, 'stock_quantity' => 10, 'is_active' => true];
        $this->putJson('/api/w03-native-products/'.$product->id, ['product_code' => $product->product_code, 'options' => [$newOption]], $this->bearer($admin))
            ->assertStatus(422)->assertJsonPath('errors.options.0', __('raonslab-travel_lab::workflow.option_delete_restricted'));
        $ordinary = $this->ordinaryOption();
        $ordinaryProduct = $ordinary->product;
        $this->actingAs($admin);
        app(ProductService::class)->update($ordinaryProduct, ['options' => [$newOption]]);
        $this->assertDatabaseMissing('ecommerce_product_options', ['id' => $ordinary->id]);
        $this->assertSame(1, $ordinaryProduct->options()->count());
        $this->assertSame($departure->product_option_id, $departure->fresh()->product_option_id);
    }

    public function test_filtered_option_removal_is_also_rejected_before_any_write(): void
    {
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $this->actingAs($this->createAdminUser(['sirsoft-ecommerce.products.update']));
        $filter = static function (array $data): array {
            $data['options'] = [];

            return $data;
        };
        HookManager::addFilter('sirsoft-ecommerce.product.filter_update_data', $filter, 10);
        try {
            try {
                app(ProductService::class)->update($product, ['options' => [['id' => $departure->product_option_id, 'stock_quantity' => 20]]]);
                $this->fail('A preceding filter removed a protected option.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('options', $e->errors());
            }
            $this->assertSame(1000, $departure->option->fresh()->stock_quantity);
        } finally {
            HookManager::removeFilter('sirsoft-ecommerce.product.filter_update_data', $filter);
        }
    }

    public function test_response_adapter_preserves_unmarked_native_conflicts(): void
    {
        Route::get('/api/w03-unrelated-conflict', fn () => response()->json(['message' => 'Native unrelated conflict'], 409))
            ->middleware(['api', TravelCatalogConflictResponse::class])->name('w03.unrelated-conflict');
        $this->getJson('/api/w03-unrelated-conflict')->assertStatus(409)->assertJsonPath('message', 'Native unrelated conflict');
    }

    public function test_departure_form_rejects_kst_today_and_accepts_next_day(): void
    {
        $departure = $this->departure();
        $admin = $this->createAdminUser(['raonslab-travel_lab.catalog.update']);
        $this->travelTo(new \DateTimeImmutable('2026-11-30 15:30:00', new \DateTimeZone('UTC')));
        $url = self::API.'/admin/catalog/'.$departure->product_id.'/departures/'.$departure->id;
        $body = ['product_option_id' => $departure->product_option_id, 'departure_date' => '2026-12-01',
            'return_date' => '2026-12-03', 'capacity' => 10, 'is_active' => true];
        $this->putJson($url, $body, $this->bearer($admin))->assertStatus(422)->assertJsonValidationErrors('departure_date');
        $body['departure_date'] = '2026-12-02';
        $this->putJson($url, $body, $this->bearer($admin))->assertOk();
        $this->assertSame('2026-12-02', $departure->fresh()->departure_date->toDateString());
        $this->travelBack();
    }

    public function test_native_option_price_stock_edit_and_add_are_allowed_when_linked_id_retained(): void
    {
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $this->actingAs($this->createAdminUser(['sirsoft-ecommerce.products.update']));
        app(ProductService::class)->update($product, ['options' => [
            ['id' => $departure->product_option_id, 'selling_price' => 13000, 'stock_quantity' => 20],
            ['option_code' => 'W03-NEW', 'option_values' => [], 'option_name' => ['ko' => '신규', 'en' => 'New'],
                'selling_price' => 14000, 'price_adjustment' => 0, 'stock_quantity' => 10, 'is_active' => true],
        ]]);
        $this->assertSame(20, $departure->option->fresh()->stock_quantity);
        $this->assertSame(13000.0, (float) $departure->option->fresh()->selling_price);
        $this->assertSame(2, $product->options()->count());
    }

    /** W03-03: KST day, rather than UTC date, governs cart and inquiry. */
    public function test_same_day_cutoff_at_kst_midnight_blocks_cart_and_existing_cart_submit(): void
    {
        $departure = $this->departure();
        $departure->forceFill(['departure_date' => '2026-12-01', 'return_date' => '2026-12-02'])->save();
        $user = $this->createUser();
        $this->travelTo(new \DateTimeImmutable('2026-11-30 14:30:00', new \DateTimeZone('UTC')));
        $cart = $this->cartId($user, $departure);
        $this->travelTo(new \DateTimeImmutable('2026-11-30 15:30:00', new \DateTimeZone('UTC')));
        $this->assertSame('2026-12-01', TravelDate::today()->toDateString());
        $this->assertSame(409, $this->httpStatusOf(fn () => app(TravelCartService::class)->add($user->id, $departure->id, 1)));
        $this->assertSame(409, $this->httpStatusOf(fn () => app(InquiryService::class)->submit($user->id, [$cart], ['name' => 'W03'], 'w03-kst-same-day')));
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $departure->forceFill(['departure_date' => '2026-12-02', 'return_date' => '2026-12-03'])->save();
        $inquiry = app(InquiryService::class)->submit($user->id, [$cart], ['name' => 'W03'], 'w03-kst-next-day');
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $inquiry->status);
        $this->travelBack();
    }

    public function test_submit_throttle_allows_normal_idempotent_retry_and_isolates_members(): void
    {
        $departure = $this->departure();
        $user = $this->createUser();
        $cart = $this->cartId($user, $departure);
        $headers = $this->bearer($user);
        $payload = ['cart_ids' => [$cart], 'contact' => ['name' => 'W03'], 'idempotency_key' => 'w03-throttle-01'];
        $first = $this->postJson(self::API.'/inquiries', $payload, $headers)->assertCreated()->json('data.id');
        for ($i = 0; $i < 9; $i++) {
            $this->postJson(self::API.'/inquiries', $payload, $headers)->assertOk()->assertJsonPath('data.id', $first);
        }
        $this->postJson(self::API.'/inquiries', $payload, $headers)->assertStatus(429)->assertHeader('Retry-After');
        $other = $this->createUser();
        $otherCart = $this->cartId($other, $departure);
        $this->postJson(self::API.'/inquiries', ['cart_ids' => [$otherCart], 'contact' => ['name' => 'Other'], 'idempotency_key' => 'w03-other-01'], $this->bearer($other))->assertCreated();
        $this->assertDatabaseCount('travel_lab_inquiries', 2);
        $this->assertSame(2, $departure->fresh()->reserved);
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
