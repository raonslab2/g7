<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Extension\HookListenerRegistrar;
use App\Extension\HookManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Listeners\BlockTravelCommerceCheckout;
use Modules\Raonslab\TravelLab\Module;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Raonslab\TravelLab\Tests\WorkflowTestCase;
use Modules\Sirsoft\Ecommerce\Exceptions\CartUnavailableException;
use Modules\Sirsoft\Ecommerce\Models\TempOrder;
use Modules\Sirsoft\Ecommerce\Services\OrderProcessingService;

class TravelCheckoutGuardTest extends WorkflowTestCase
{
    /**
     * @scenario flow=checkout_guard
     *
     * @effects native_checkout_rejected, no_temp_order_no_order_no_payment, commerce_stock_unchanged
     */
    public function test_native_checkout_rejects_travel_cart_before_creating_any_trade_rows(): void
    {
        $user = $this->createUser();
        $departure = $this->departure();
        $cart = app(TravelCartService::class)->add($user->id, $departure->id, 2);
        Route::prefix('api/modules/sirsoft-ecommerce')->name('api.modules.sirsoft-ecommerce.')
            ->middleware('api')->group(base_path('modules/_bundled/sirsoft-ecommerce/src/routes/api.php'));
        $token = $user->createToken('native-checkout')->plainTextToken;
        Auth::forgetGuards();
        $this->postJson('/api/modules/sirsoft-ecommerce/checkout', ['item_ids' => [$cart['items'][0]['id']]], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(400)->assertJsonPath('errors.has_restriction_issue', true);
        Auth::forgetGuards();
        $this->postJson('/api/modules/sirsoft-ecommerce/checkout', ['direct_items' => [[
            'product_id' => $departure->product_id, 'product_option_id' => $departure->product_option_id, 'quantity' => 1,
        ]]], ['Authorization' => 'Bearer '.$token])->assertStatus(400);
        foreach (['ecommerce_temp_orders', 'ecommerce_orders', 'ecommerce_order_payments'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(1000, $departure->option->fresh()->stock_quantity);
        $this->assertSame(0, $departure->fresh()->reserved);
        $this->assertDatabaseCount('ecommerce_carts', 1);
    }

    /**
     * @scenario flow=checkout_guard
     *
     * @effects direct_items_cannot_bypass_guard, native_order_hook_precedes_price_and_trade_writes
     */
    public function test_unpersisted_temp_order_with_travel_items_is_rejected_before_order_or_payment(): void
    {
        $departure = $this->departure();
        $source = new TempOrder(['items' => [['product_id' => $departure->product_id, 'product_option_id' => $departure->product_option_id, 'quantity' => 1]]]);
        try {
            app(OrderProcessingService::class)->createFromTempOrder($source, [], [], 'dbank', 1);
            $this->fail('Travel product reached native order creation.');
        } catch (CartUnavailableException $exception) {
            $this->assertTrue($exception->hasRestrictionIssue());
        }
        foreach (['ecommerce_temp_orders', 'ecommerce_orders', 'ecommerce_order_payments'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(1000, $departure->option->fresh()->stock_quantity);
    }

    /**
     * @scenario flow=checkout_guard
     *
     * @effects product_and_option_membership_checked, ordinary_product_unaffected, blocking_hooks_sync
     */
    public function test_guard_uses_product_and_option_membership_and_preserves_ordinary_commerce(): void
    {
        // Native core may already have loaded an older installed Module declaration.
        // Inspect the current bundled entry in a clean process, without redefining or
        // accepting the installed entry in this long-lived SQLite fixture application.
        $canonicalModule = realpath(dirname(__DIR__, 2).'/module.php');
        $canonicalListener = realpath(dirname(__DIR__, 2).'/src/Listeners/BlockTravelCommerceCheckout.php');
        $parentModuleFile = class_exists(Module::class, false) ? (new \ReflectionClass(Module::class))->getFileName() : null;
        $code = 'require '.var_export(base_path('vendor/autoload.php'), true).'; require '.var_export($canonicalModule, true).'; '
            .'require '.var_export($canonicalListener, true).'; '
            .'$module = new Modules\\Raonslab\\TravelLab\\Module; '
            .'$source = (new ReflectionClass($module))->getFileName(); '
            .'$listener = (new ReflectionClass(Modules\\Raonslab\\TravelLab\\Listeners\\BlockTravelCommerceCheckout::class))->getFileName(); '
            .'echo json_encode(["source" => realpath($source), "sha256" => hash_file("sha256", $source), '
            .'"listeners" => $module->getHookListeners(), "listener_source" => realpath($listener), '
            .'"listener_sha256" => hash_file("sha256", $listener), '
            .'"hooks" => Modules\\Raonslab\\TravelLab\\Listeners\\BlockTravelCommerceCheckout::getSubscribedHooks()], JSON_THROW_ON_ERROR);';
        $process = proc_open([PHP_BINARY, '-r', $code], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors);
        $declaration = json_decode($output, true, 32, JSON_THROW_ON_ERROR);
        $this->assertSame($canonicalModule, $declaration['source']);
        $this->assertSame(hash_file('sha256', $canonicalModule), $declaration['sha256']);
        $this->assertSame($canonicalListener, $declaration['listener_source']);
        $this->assertSame(hash_file('sha256', $canonicalListener), $declaration['listener_sha256']);
        $this->assertSame($parentModuleFile, class_exists(Module::class, false) ? (new \ReflectionClass(Module::class))->getFileName() : null);
        $this->assertContains(BlockTravelCommerceCheckout::class, $declaration['listeners']);
        $this->assertSame($canonicalListener, (new \ReflectionClass(BlockTravelCommerceCheckout::class))->getFileName());
        $this->assertSame($declaration['hooks'], BlockTravelCommerceCheckout::getSubscribedHooks());
        HookListenerRegistrar::register(BlockTravelCommerceCheckout::class, 'raonslab-travel_lab');
        $departure = $this->departure();
        $ordinary = $this->ordinaryOption();
        app(BlockTravelCommerceCheckout::class)->handle(collect([['product_id' => $ordinary->product_id, 'product_option_id' => $ordinary->id]]));
        try {
            // Malformed product/option pairing must not disguise a travel option as ordinary commerce.
            app(BlockTravelCommerceCheckout::class)->handle(collect([['product_id' => $ordinary->product_id, 'product_option_id' => $departure->product_option_id]]));
            $this->fail('Travel option membership was bypassed.');
        } catch (CartUnavailableException $exception) {
            $this->assertTrue($exception->hasRestrictionIssue());
        }
        try {
            // Product membership alone blocks a travel product paired with an ordinary option.
            app(BlockTravelCommerceCheckout::class)->handle(collect([['product_id' => $departure->product_id, 'product_option_id' => $ordinary->id]]));
            $this->fail('Travel product membership was bypassed.');
        } catch (CartUnavailableException $exception) {
            $this->assertTrue($exception->hasRestrictionIssue());
        }
        foreach ($declaration['hooks'] as $name => $hook) {
            $this->assertTrue($hook['sync']);
            // Exercise native registrar callbacks, not just matching declarations.
            HookManager::doAction($name, collect([['product_id' => $ordinary->product_id, 'product_option_id' => $ordinary->id]]));
            try {
                HookManager::doAction($name, collect([['product_id' => $ordinary->product_id, 'product_option_id' => $departure->product_option_id]]));
                $this->fail('Declared native hook did not reject travel option membership: '.$name);
            } catch (CartUnavailableException $exception) {
                $this->assertTrue($exception->hasRestrictionIssue());
            }
        }
    }
}
