<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Raonslab\TravelLab\Database\Seeders\DatabaseSeeder;
use Modules\Raonslab\TravelLab\Database\Seeders\SyntheticCatalogSeeder;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Models\InquiryItem;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Tests\ModuleTestCase;
use Modules\Sirsoft\Ecommerce\Database\Seeders\SequenceSeeder;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;

class SchemaAndSeederTest extends ModuleTestCase
{
    /** @effects schema_up_and_down, foreign_keys_registered */
    public function test_travel_migration_is_reversible_and_has_native_foreign_keys(): void
    {
        $tables = ['travel_lab_products', 'travel_lab_departures', 'travel_lab_inquiries', 'travel_lab_inquiry_items'];
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertNotEmpty(Schema::getForeignKeys($table));
        }
        $files = glob(dirname(__DIR__, 2).'/database/migrations/*.php');
        rsort($files);
        foreach ($files as $file) {
            (require $file)->down();
        }
        foreach ($tables as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        sort($files);
        foreach ($files as $file) {
            (require $file)->up();
        }
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
    }

    /** @effects unique_product_identity */
    public function test_duplicate_travel_identity_is_rejected_by_database(): void
    {
        [$travel] = $this->createTravel();
        $this->expectException(QueryException::class);
        $travel->replicate()->save();
    }

    /** @effects unique_departure_option */
    public function test_duplicate_departure_option_is_rejected_by_database(): void
    {
        [, $departure] = $this->createTravel();
        $this->expectException(QueryException::class);
        $departure->replicate()->save();
    }

    /** @effects inquiry_contract_relations, inquiry_enum_round_trip, per_user_idempotency */
    public function test_inquiry_models_share_contract_and_unique_per_user_idempotency(): void
    {
        [, $departure] = $this->createTravel();
        $user = User::create(['name' => 'Test traveler', 'email' => 'traveler@example.test', 'password' => 'password']);
        $inquiry = Inquiry::create([
            'user_id' => $user->id, 'idempotency_key' => 'repeat-key', 'payload_hash' => hash('sha256', 'payload'),
            'status' => InquiryStatus::TEST_INQUIRY, 'total_amount' => 200000, 'currency_code' => 'KRW',
            'contact' => ['name' => 'Synthetic traveler'],
        ]);
        $item = InquiryItem::create([
            'inquiry_id' => $inquiry->id, 'departure_id' => $departure->id, 'product_id' => $departure->product_id,
            'product_option_id' => $departure->product_option_id, 'quantity' => 2,
            'unit_price' => 100000, 'line_total' => 200000, 'product_name' => ['ko' => '테스트', 'en' => 'Test'],
            'departure_date' => '2026-11-01',
        ]);
        $this->assertSame(InquiryStatus::TEST_INQUIRY, $inquiry->fresh()->status);
        $this->assertSame($user->id, $inquiry->user->id);
        $this->assertSame($item->id, $inquiry->items->first()->id);
        $this->assertSame($departure->id, $item->departure->id);
        $this->assertSame($inquiry->id, $item->inquiry->id);
        $other = User::create(['name' => 'Other traveler', 'email' => 'other@example.test', 'password' => 'password']);
        $copy = $inquiry->replicate();
        $copy->user_id = $other->id;
        $copy->save();
        $this->assertSame(2, Inquiry::count());
        $this->expectException(QueryException::class);
        $inquiry->replicate()->save();
    }

    /** @effects default_seed_no_samples, explicit_sample_seed */
    public function test_default_database_seeder_skips_samples_and_explicit_sample_flag_generates_catalog(): void
    {
        $seeder = $this->app->make(DatabaseSeeder::class)->setContainer($this->app);
        $seeder->run();
        $this->assertSame(0, Product::count());
        $this->assertSame(0, TravelProduct::count());
        $this->assertSame(0, Departure::count());
        Artisan::call('db:seed', ['--class' => SequenceSeeder::class]);
        $seeder->setIncludeSample(true)->run(); // Same official --sample flag passed by SeedModuleCommand.
        $this->assertSame(8, Product::count());
        $this->assertSame(8, TravelProduct::count());
        $this->assertSame(24, Departure::count());
    }

    /** @effects seed_reproducible, ecommerce_service_products_and_options, seed_preserves_operator_edits, no_order_payment_inquiry_writes */
    public function test_synthetic_catalog_seeder_rerun_preserves_ids_stock_metadata_and_reservations(): void
    {
        Artisan::call('db:seed', ['--class' => SequenceSeeder::class]);
        $this->app->make(SyntheticCatalogSeeder::class)->run();
        $first = TravelProduct::orderBy('id')->get();
        $this->assertCount(8, $first);
        $this->assertSame(24, Departure::count());
        $identities = $first->pluck('product_id')->all();
        $departureIds = Departure::orderBy('id')->pluck('id')->all();
        $optionIds = ProductOption::orderBy('id')->pluck('id')->all();
        $first->first()->update(['summary' => ['ko' => '운영자 수정', 'en' => 'Operator edit']]);
        $departure = Departure::first();
        $departure->forceFill(['reserved' => 2])->save();
        $this->app->make(SyntheticCatalogSeeder::class)->run();
        $this->assertSame($identities, TravelProduct::orderBy('id')->pluck('product_id')->all());
        $this->assertSame($departureIds, Departure::orderBy('id')->pluck('id')->all());
        $this->assertSame($optionIds, ProductOption::orderBy('id')->pluck('id')->all());
        $this->assertSame('Operator edit', $first->first()->fresh()->summary['en']);
        $this->assertSame(2, $departure->fresh()->reserved);
        foreach (Departure::with(['option', 'product'])->get() as $row) {
            $this->assertSame($row->product_id, $row->option->product_id);
            $this->assertGreaterThanOrEqual($row->capacity, $row->option->stock_quantity);
            $this->assertTrue($row->product->has_options);
            $this->assertNotNull($row->product->shipping_policy_id);
            $this->assertFalse($row->product->shippingPolicy->is_default);
            $this->assertSame(0, $row->product->shippingPolicy->countrySettings()->count());
        }
        foreach (['ecommerce_orders', 'ecommerce_order_payments', 'travel_lab_inquiries'] as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
        $this->assertSame(count($identities), Product::count());
    }
}
