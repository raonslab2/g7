<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Enums\PermissionType;
use App\Extension\HookListenerRegistrar;
use App\Extension\HookManager;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Listeners\ProtectTravelCommerceCatalog;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Tests\ModuleTestCase;
use Modules\Sirsoft\Ecommerce\Exceptions\ProductHasOrderHistoryException;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Services\ProductService;

/** Nonauthor HTTP checks: real SQLite/native identities, provider and Sanctum. */
class W03CatalogRepairTest extends ModuleTestCase
{
    private const BASE = '/api/modules/raonslab-travel_lab';

    private function actor(array $permissions, string $identifier = 'manager'): User
    {
        $user = User::create(['name' => 'Synthetic catalogue reviewer', 'email' => bin2hex(random_bytes(8)).'@example.test', 'password' => 'password']);
        if ($permissions !== []) {
            $role = Role::firstOrCreate(['identifier' => $identifier === 'manager' ? 'manager' : $identifier.'-'.bin2hex(random_bytes(5))], ['name' => ['ko' => '검증 관리자', 'en' => 'Review manager']]);
            $user->roles()->attach($role->id);
            foreach ($permissions as $identifier) {
                $permission = Permission::firstOrCreate(['identifier' => $identifier], ['name' => ['ko' => $identifier, 'en' => $identifier], 'type' => PermissionType::Admin]);
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }

        return $user;
    }

    private function authenticate(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$user->createToken('catalogue-repair-review')->plainTextToken);
    }

    /** @return array{0: Product, 1: ProductOption} */
    private function unmapped(): array
    {
        [$metadata, $departure] = $this->createTravel();
        $product = $metadata->product;
        $option = $departure->option;
        $departure->delete();
        $metadata->delete();

        return [$product, $option];
    }

    private function payload(Product $product): array
    {
        return ['product_id' => $product->id, 'region' => 'jeju', 'theme' => 'nature',
            'duration_days' => 2, 'summary' => ['ko' => '합성 일정', 'en' => 'Synthetic itinerary']];
    }

    private function writeActor(): User
    {
        return $this->actor(['admin.access', 'raonslab-travel_lab.catalog.read', 'raonslab-travel_lab.catalog.update'], 'catalogue-admin');
    }

    public function test_registration_preserves_native_identity_options_and_price_and_defaults_unpublished(): void
    {
        [$product, $option] = $this->unmapped();
        $beforeProduct = $product->getAttributes();
        $beforeOption = $option->getAttributes();
        $this->authenticate($this->writeActor());
        $response = $this->postJson(self::BASE.'/admin/catalog', $this->payload($product));
        $response->assertCreated()->assertJsonPath('data.id', $product->id)->assertJsonPath('data.published', false)
            ->assertJsonPath('data.options.0.id', $option->id)->assertJsonPath('data.options.0.stock_quantity', 20);
        $this->assertSame($beforeProduct, $product->fresh()->getAttributes());
        $this->assertSame($beforeOption, $option->fresh()->getAttributes());
        $this->assertDatabaseCount('ecommerce_products', 1);
        $this->assertDatabaseCount('ecommerce_product_options', 1);
        $this->assertDatabaseCount('ecommerce_orders', 0);
        $this->assertDatabaseCount('travel_lab_products', 1);
        $this->assertDatabaseCount('travel_lab_departures', 0);
        $this->postJson(self::BASE.'/admin/catalog', $this->payload($product))->assertStatus(409);
        $this->assertDatabaseCount('travel_lab_products', 1);
        $this->getJson(self::BASE.'/catalog/'.$product->id)->assertNotFound();
    }

    public function test_guest_member_and_read_only_manager_cannot_register_but_permissioned_manager_can(): void
    {
        [$product] = $this->unmapped();
        $this->postJson(self::BASE.'/admin/catalog', $this->payload($product))->assertUnauthorized();
        $this->authenticate($this->actor([]));
        $this->getJson(self::BASE.'/admin/catalog/candidates')->assertForbidden();
        $this->postJson(self::BASE.'/admin/catalog', $this->payload($product))->assertForbidden();
        $this->authenticate($this->actor(['admin.access', 'raonslab-travel_lab.catalog.read']));
        $this->getJson(self::BASE.'/admin/catalog/candidates')->assertOk();
        $this->postJson(self::BASE.'/admin/catalog', $this->payload($product))->assertForbidden();
        $this->assertDatabaseCount('travel_lab_products', 0);
        $writer = $this->actor(['admin.access', 'raonslab-travel_lab.catalog.read', 'raonslab-travel_lab.catalog.update']);
        $this->assertTrue($writer->hasRole('manager'));
        $this->authenticate($writer);
        $this->postJson(self::BASE.'/admin/catalog', $this->payload($product))->assertCreated();
    }

    public function test_itinerary_accepts_valid_json_string_and_rejects_invalid_or_extra_nested_fields(): void
    {
        [$product] = $this->unmapped();
        $this->authenticate($this->writeActor());
        $itinerary = [['day' => 1, 'title' => ['ko' => '도착', 'en' => 'Arrival'], 'description' => ['ko' => '산책', 'en' => 'Walk']]];
        $this->postJson(self::BASE.'/admin/catalog', [...$this->payload($product), 'itinerary' => json_encode($itinerary, JSON_THROW_ON_ERROR)])
            ->assertCreated()->assertJsonPath('data.itinerary_translations', $itinerary);
        $uri = self::BASE.'/admin/catalog/'.$product->id;
        foreach (['not json', '[{"day":1}]', json_encode([['day' => 1, 'title' => ['ko' => '도착', 'en' => 'Arrival'], 'price' => 1]], JSON_THROW_ON_ERROR)] as $invalid) {
            $this->patchJson($uri, ['itinerary' => $invalid])->assertUnprocessable();
            $this->assertSame($itinerary, TravelProduct::where('product_id', $product->id)->firstOrFail()->itinerary);
        }
        $this->patchJson($uri, ['itinerary' => json_encode([['day' => 2, 'title' => ['ko' => '귀환', 'en' => 'Return']]], JSON_THROW_ON_ERROR)])->assertOk();
    }

    public function test_updates_reject_product_reassignment_and_unknown_money_fields_without_native_changes(): void
    {
        [$travel] = $this->createTravel();
        [$other] = $this->unmapped();
        $before = $travel->product->getAttributes();
        $this->authenticate($this->writeActor());
        $uri = self::BASE.'/admin/catalog/'.$travel->product_id;
        foreach (['product_id' => $other->id, 'selling_price' => 1, 'unit_price' => 1, 'amount' => 1] as $key => $value) {
            $this->patchJson($uri, [$key => $value])->assertUnprocessable()->assertJsonValidationErrors($key);
            $this->assertSame($before, $travel->product->fresh()->getAttributes());
            $this->assertSame($travel->product_id, $travel->fresh()->product_id);
        }
        $this->patchJson($uri, ['region' => 'busan', 'summary' => ['ko' => '수정 일정', 'en' => 'Updated itinerary']])->assertOk()->assertJsonPath('data.region', 'busan');
        $this->getJson($uri)->assertOk()->assertJsonPath('data.summary_translations.ko', '수정 일정');
    }

    public function test_candidates_include_actual_option_identity_and_exclude_mapped_and_deleted_products(): void
    {
        [$mapped] = $this->createTravel();
        [$available, $option] = $this->unmapped();
        [$deleted] = $this->unmapped();
        $deleted->delete();
        $this->authenticate($this->writeActor());
        $response = $this->getJson(self::BASE.'/admin/catalog/candidates?q='.rawurlencode('제주'));
        $response->assertOk()->assertJsonPath('data.data.0.id', $available->id)
            ->assertJsonPath('data.data.0.options.0.id', $option->id)->assertJsonPath('data.data.0.options.0.stock_quantity', 20);
        $this->assertSame([$available->id], array_column($response->json('data.data'), 'id'));
        $this->assertNotContains($mapped->product_id, array_column($response->json('data.data'), 'id'));
        $this->getJson(self::BASE.'/admin/catalog/candidates?q='.rawurlencode($available->product_code))->assertOk()->assertJsonPath('data.data.0.id', $available->id);
    }

    public function test_korean_keyword_search_decodes_json_title_and_description_without_matching_other_products(): void
    {
        [$titleMatch] = $this->createTravel([], ['name' => ['ko' => '제주 섬 여행', 'en' => 'Island journey']]);
        [$descriptionMatch] = $this->createTravel([], ['name' => ['ko' => '바다 여행', 'en' => 'Sea journey'], 'description' => ['ko' => '제주 산책', 'en' => 'Island walk']]);
        $this->createTravel([], ['name' => ['ko' => '부산 여행', 'en' => 'Busan journey'], 'description' => ['ko' => '역사 산책', 'en' => 'Heritage walk']]);
        $response = $this->getJson(self::BASE.'/catalog?q='.rawurlencode('제주'));
        $response->assertOk();
        $this->assertSame([$titleMatch->product_id, $descriptionMatch->product_id], array_column($response->json('data.data'), 'id'));
        $this->getJson(self::BASE.'/catalog?q='.rawurlencode('없는검색어'))->assertOk()->assertJsonCount(0, 'data.data');
    }

    public function test_public_detail_exposes_disabled_future_dates_but_discovery_requires_one_available_date(): void
    {
        [$travel, $available] = $this->createTravel([], ['selling_price' => 120000]);
        $disabled = [];
        foreach ([['reserved' => 20], ['stock_quantity' => 0], ['is_active' => false], ['departure_date' => '2026-10-08']] as $index => $attributes) {
            $option = $available->option->replicate();
            $option->option_code = 'REVIEW-DATE-'.$index;
            $option->price_adjustment = -100000;
            if (array_key_exists('stock_quantity', $attributes)) {
                $option->stock_quantity = $attributes['stock_quantity'];
                unset($attributes['stock_quantity']);
            }
            $option->save();
            $departure = $available->replicate();
            $departure->product_option_id = $option->id;
            $departure->forceFill($attributes)->save();
            if ($index < 2) {
                $disabled[] = $departure->id;
            }
        }
        $detail = $this->getJson(self::BASE.'/catalog/'.$travel->product_id)->assertOk()->assertJsonPath('data.from_price', 120000);
        $this->assertSame([$available->id, ...$disabled], array_column($detail->json('data.departures'), 'id'));
        $this->assertSame([20, 0, 0], array_column($detail->json('data.departures'), 'available'));
        $available->forceFill(['reserved' => 20])->save();
        $this->getJson(self::BASE.'/catalog')->assertOk()->assertJsonCount(0, 'data.data');
        $this->getJson(self::BASE.'/catalog/'.$travel->product_id)->assertNotFound();
    }

    public function test_korean_business_day_boundary_excludes_today_and_validates_new_departures_in_kst(): void
    {
        config(['app.timezone' => 'UTC', 'raonslab-travel_lab.catalog.business_timezone' => 'Asia/Seoul']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-09 16:00:00', 'UTC'));
        try {
            [$today] = $this->createTravel([], [], ['departure_date' => '2026-10-10', 'return_date' => '2026-10-11']);
            [$tomorrow, $departure] = $this->createTravel([], [], ['departure_date' => '2026-10-11', 'return_date' => '2026-10-12']);
            $result = $this->getJson(self::BASE.'/catalog')->assertOk();
            $this->assertSame([$tomorrow->product_id], array_column($result->json('data.data'), 'id'));
            $this->getJson(self::BASE.'/catalog/'.$today->product_id)->assertNotFound();
            $this->authenticate($this->writeActor());
            $option = $departure->option->replicate();
            $option->option_code = 'KST-NEW';
            $option->save();
            $payload = ['product_option_id' => $option->id, 'departure_date' => '2026-10-10', 'return_date' => '2026-10-11', 'capacity' => 10];
            $uri = self::BASE.'/admin/catalog/'.$tomorrow->product_id.'/departures';
            $this->postJson($uri, $payload)->assertUnprocessable()->assertJsonValidationErrors('departure_date');
            $this->postJson($uri, [...$payload, 'departure_date' => '2026-10-11', 'return_date' => '2026-10-12'])->assertOk();
            $this->assertDatabaseCount('travel_lab_departures', 3);
            $this->assertSame('sqlite', DB::getDriverName());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_actual_bundled_module_declares_synchronous_guard_and_native_registrar_enforces_it(): void
    {
        // The installed Module class may differ until lead resync. A clean PHP
        // subprocess loads the exact bundled entry, without booting Laravel/DB.
        $canonical = dirname(__DIR__, 2).'/module.php';
        $code = 'require '.var_export(base_path('vendor/autoload.php'), true).'; require '.var_export($canonical, true).'; '
            .'echo json_encode((new Modules\\Raonslab\\TravelLab\\Module)->getHookListeners(), JSON_THROW_ON_ERROR);';
        $process = proc_open([PHP_BINARY, '-r', $code], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors);
        $listeners = json_decode($output, true, 32, JSON_THROW_ON_ERROR);
        $this->assertContains(ProtectTravelCommerceCatalog::class, $listeners);
        $this->assertTrue(ProtectTravelCommerceCatalog::getSubscribedHooks()['sirsoft-ecommerce.product.before_delete']['sync']);
        $this->assertTrue(ProtectTravelCommerceCatalog::getSubscribedHooks()['sirsoft-ecommerce.product.before_update']['sync']);
        [$travel] = $this->createTravel();
        HookManager::resetAll();
        HookListenerRegistrar::clear();
        try {
            foreach ($listeners as $listener) {
                HookListenerRegistrar::register($listener, 'raonslab-travel_lab');
            }
            try {
                app(ProductService::class)->delete($travel->product);
                $this->fail('The actual module-declared/native-registered guard did not stop travel deletion.');
            } catch (ProductHasOrderHistoryException $exception) {
                $this->assertSame(__('raonslab-travel_lab::workflow.product_delete_restricted'), $exception->getMessage());
            }
            $this->assertNotNull(Product::find($travel->product_id));
            $this->assertDatabaseCount('travel_lab_departures', 1);
        } finally {
            HookManager::resetAll();
            HookListenerRegistrar::clear();
        }
    }
}
