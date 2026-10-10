<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Extension\HookListenerRegistrar;
use App\Extension\HookManager;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelThrottleRequests;
use Modules\Raonslab\TravelLab\Listeners\ProtectTravelCommerceCatalog;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Module;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Raonslab\TravelLab\Tests\WorkflowTestCase;
use Modules\Sirsoft\Ecommerce\Models\Cart;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Services\ProductService;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * W03 nonauthor security recheck at the fixed SHA 7de0c444. These are SQLite domain probes only; they
 * are not MySQL row-lock or HTTP-overlap evidence (that is in tests/evidence/w03-recheck).
 *
 * The time boundary uses the real TravelDate with the app timezone left at UTC. Only the clock moves,
 * via travelTo.
 */
class W03SecurityRecheckTest extends WorkflowTestCase
{
    private function httpStatus(callable $operation): int
    {
        try {
            $operation();
        } catch (HttpResponseException $e) {
            return $e->getResponse()->getStatusCode();
        }

        return 200;
    }

    private function cartFor(User $user, Departure $departure, int $quantity = 1): int
    {
        app(TravelCartService::class)->add($user->id, $departure->id, $quantity);

        return (int) Cart::where('user_id', $user->id)->where('product_option_id', $departure->product_option_id)->sole()->id;
    }

    /** @return array<string, array{string, string, bool}> UTC instant, departure date, available */
    public static function kstBoundaries(): array
    {
        return [
            'UTC 14:59:59 = KST 23:59:59 the day before: a KST-tomorrow departure is still available' => ['2026-11-30 14:59:59', '2026-12-01', true],
            'UTC 15:00:00 = KST 00:00:00: a KST-today departure is closed' => ['2026-11-30 15:00:00', '2026-12-01', false],
            'UTC 23:59:59 = KST 08:59:59: a KST-today departure is still closed while the UTC date lags' => ['2026-11-30 23:59:59', '2026-12-01', false],
            'UTC 00:00:00 = KST 09:00:00: a KST-today departure stays closed' => ['2026-12-01 00:00:00', '2026-12-01', false],
            'UTC 23:59:59 = KST 08:59:59: the next KST day is available' => ['2026-11-30 23:59:59', '2026-12-02', true],
        ];
    }

    #[DataProvider('kstBoundaries')]
    public function test_kst_boundary_closes_cart_and_submit_without_changing_app_timezone(string $utc, string $date, bool $available): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $departure = $this->departure();
        $departure->forceFill(['departure_date' => $date, 'return_date' => $date])->save();
        $user = $this->createUser();
        $this->travelTo(new \DateTimeImmutable('2026-11-29 00:00:00', new \DateTimeZone('UTC')));
        $cart = $this->cartFor($user, $departure);

        $this->travelTo(new \DateTimeImmutable($utc, new \DateTimeZone('UTC')));
        $this->assertSame('UTC', now()->getTimezone()->getName(), 'global clock timezone must stay UTC');
        $submit = $this->httpStatus(fn () => app(InquiryService::class)->submit($user->id, [$cart], ['name' => 'W03R'], 'w03r-'.md5($utc.$date)));

        if ($available) {
            $this->assertSame(200, $submit);
            $this->assertSame(1, $departure->fresh()->reserved);
        } else {
            $this->assertSame(409, $submit);
            $this->assertSame(0, $departure->fresh()->reserved);
            $this->assertDatabaseCount('travel_lab_inquiries', 0);
            $this->assertDatabaseHas('ecommerce_carts', ['id' => $cart]);
            $this->assertSame(409, $this->httpStatus(fn () => app(TravelCartService::class)->add($user->id, $departure->id, 1)));
        }
        $this->travelBack();
    }

    public function test_orphaned_cart_for_deactivated_departure_or_hidden_product_is_rejected_without_writes(): void
    {
        $user = $this->createUser();
        $departure = $this->departure(capacity: 3);
        $cart = $this->cartFor($user, $departure);
        $departure->forceFill(['is_active' => false])->save();
        $this->assertSame(409, $this->httpStatus(fn () => app(InquiryService::class)->submit($user->id, [$cart], ['name' => 'W03R'], 'w03r-orphan-1')));
        $departure->forceFill(['is_active' => true])->save();
        Product::whereKey($departure->product_id)->update(['display_status' => 'hidden']);
        $this->assertSame(409, $this->httpStatus(fn () => app(InquiryService::class)->submit($user->id, [$cart], ['name' => 'W03R'], 'w03r-orphan-2')));
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('travel_lab_inquiries', 0);
        $this->assertDatabaseHas('ecommerce_carts', ['id' => $cart]);
    }

    /** The actual module declaration, not a hand-registered closure, must wire every guard synchronously. */
    public function test_actual_module_declares_protect_listener_and_registrar_wires_sync_and_final_filter(): void
    {
        // Native boot may already have loaded the installed entry under this exact
        // class name. Read the canonical bundled declaration in a clean process;
        // never redefine the entry in this long-lived SQLite test application.
        $canonical = realpath(dirname(__DIR__, 2).'/module.php');
        $sourceHash = hash_file('sha256', $canonical);
        $parentModuleFile = class_exists(Module::class, false) ? (new \ReflectionClass(Module::class))->getFileName() : null;
        $code = 'require '.var_export(base_path('vendor/autoload.php'), true).'; require '.var_export($canonical, true).'; '
            .'$module = new Modules\\Raonslab\\TravelLab\\Module; '
            .'$source = (new ReflectionClass($module))->getFileName(); '
            .'echo json_encode(["source" => realpath($source), "sha256" => hash_file("sha256", $source), "listeners" => $module->getHookListeners()], JSON_THROW_ON_ERROR);';
        $process = proc_open([PHP_BINARY, '-r', $code], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors);
        $declaration = json_decode($output, true, 32, JSON_THROW_ON_ERROR);
        $this->assertSame($canonical, $declaration['source']);
        $this->assertSame($sourceHash, $declaration['sha256']);
        $this->assertSame($parentModuleFile, class_exists(Module::class, false) ? (new \ReflectionClass(Module::class))->getFileName() : null);
        $this->assertContains(ProtectTravelCommerceCatalog::class, $declaration['listeners']);
        $hooks = ProtectTravelCommerceCatalog::getSubscribedHooks();
        $this->assertTrue($hooks['sirsoft-ecommerce.product.before_delete']['sync']);
        $this->assertTrue($hooks['sirsoft-ecommerce.product.before_update']['sync']);
        $this->assertSame('filter', $hooks['sirsoft-ecommerce.product.filter_update_data']['type']);
        $this->assertSame(PHP_INT_MAX, $hooks['sirsoft-ecommerce.product.filter_update_data']['priority']);
        HookListenerRegistrar::register(ProtectTravelCommerceCatalog::class, 'raonslab-travel_lab');

        // A later native filter (lower priority number = earlier) that removes a linked option must still be rejected.
        $departure = $this->departure();
        $product = Product::findOrFail($departure->product_id);
        $strip = fn (array $data) => [...$data, 'options' => []];
        HookManager::addFilter('sirsoft-ecommerce.product.filter_update_data', $strip, 50);
        $this->actingAs($this->createAdminUser(['sirsoft-ecommerce.products.update']));
        try {
            app(ProductService::class)->update($product, ['name' => ['ko' => 'x', 'en' => 'x'], 'options' => [['id' => $departure->product_option_id]]]);
            $this->fail('filtered option removal was not rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('options', $e->errors());
        }
        $this->assertNotSame(['ko' => 'x', 'en' => 'x'], $product->fresh()->name);
        $this->assertNotNull($departure->option()->first());
        HookManager::removeFilter('sirsoft-ecommerce.product.filter_update_data', $strip);
    }

    /** Route registration contract for the per-actor throttles the live probe exhausted. */
    public function test_workflow_routes_carry_distinct_per_actor_throttle_buckets(): void
    {
        $expect = [
            'cart.index' => [TravelThrottleRequests::with(120, 1, 'travel-lab-workflow:')],
            'cart.store' => [TravelThrottleRequests::with(120, 1, 'travel-lab-workflow:'), TravelThrottleRequests::with(60, 1, 'travel-lab-cart:')],
            'cart.update' => [TravelThrottleRequests::with(60, 1, 'travel-lab-cart:')],
            'cart.destroy' => [TravelThrottleRequests::with(60, 1, 'travel-lab-cart:')],
            'inquiries.store' => [TravelThrottleRequests::with(10, 1, 'travel-lab-submit:')],
            'inquiries.cancel' => [TravelThrottleRequests::with(20, 1, 'travel-lab-cancel:')],
            'admin.inquiries.update' => [TravelThrottleRequests::with(60, 1, 'travel-lab-admin:'), 'permission:admin,raonslab-travel_lab.inquiries.update'],
        ];
        foreach ($expect as $name => $middleware) {
            $route = Route::getRoutes()->getByName('api.modules.raonslab-travel_lab.'.$name);
            $this->assertNotNull($route, $name);
            $gathered = $route->gatherMiddleware();
            $this->assertContains('auth:sanctum', $gathered, $name);
            foreach ($middleware as $m) {
                $this->assertContains($m, $gathered, $name.' missing '.$m);
            }
        }
    }
}
