<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Enums\PermissionType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Tests\ModuleTestCase;

class CatalogApiTest extends ModuleTestCase
{
    private const BASE = '/api/modules/raonslab-travel_lab';

    /** @effects api_resource_envelope, named_routes, departures_resource_shape */
    public function test_public_catalog_uses_native_resource_envelope_and_ecommerce_identity(): void
    {
        [$travel, $departure] = $this->createTravel();
        $response = $this->getJson(self::BASE.'/catalog');
        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.data.0.id', $travel->product_id)
            ->assertJsonStructure(['data' => ['data' => [['id', 'title', 'region', 'theme', 'duration_days', 'summary', 'itinerary', 'from_price', 'currency_code', 'image_url', 'departures' => [['id', 'product_id', 'product_option_id', 'departure_date', 'return_date', 'available', 'unit_price', 'currency_code']]]]]]);
        $this->getJson(self::BASE.'/catalog/'.$travel->product_id)->assertOk()->assertJsonPath('data.id', $travel->product_id);
        $this->getJson(self::BASE.'/catalog/'.$travel->product_id.'/departures')->assertOk()->assertJsonPath('data.0.id', $departure->id)->assertJsonPath('data.0.unit_price', 100000);
        $this->getJson(self::BASE.'/facets')->assertOk()->assertJsonPath('data.region.0', 'jeju');
        foreach (['catalog.index', 'catalog.show', 'catalog.departures', 'catalog.facets', 'admin.catalog.index', 'admin.catalog.update', 'admin.catalog.departures.index', 'admin.catalog.departures.store', 'admin.catalog.departures.update'] as $name) {
            $this->assertTrue(Route::has('api.modules.raonslab-travel_lab.'.$name));
        }
    }

    /** @effects api_not_found, api_validation_422 */
    public function test_public_api_returns_translated_404_and_invalid_filter_422(): void
    {
        [$hidden] = $this->createTravel(['published' => false]);
        $this->getJson(self::BASE.'/catalog/'.$hidden->product_id)->assertNotFound()->assertJsonPath('success', false);
        $this->getJson(self::BASE.'/catalog?date_from=2026-02-30')->assertUnprocessable()->assertJsonValidationErrors('date_from');
    }

    private function admin(array $permissions): User
    {
        $user = User::create(['name' => 'Synthetic admin', 'email' => 'admin-'.bin2hex(random_bytes(4)).'@example.test', 'password' => 'password']);
        $role = Role::create(['identifier' => 'travel-test-'.bin2hex(random_bytes(4)), 'name' => ['ko' => '테스트 관리자', 'en' => 'Test admin']]);
        $user->roles()->attach($role->id);
        foreach (['admin.access', ...$permissions] as $identifier) {
            $permission = Permission::firstOrCreate(['identifier' => $identifier], ['name' => ['ko' => $identifier, 'en' => $identifier], 'type' => PermissionType::Admin]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return $user;
    }

    /** @effects permissioned_admin_reads_hidden_catalog, permissioned_admin_writes_departure, reserved_request_rejected */
    public function test_permissioned_admin_reads_hidden_catalog_and_updates_departure_with_real_token(): void
    {
        [$travel, $departure] = $this->createTravel(['published' => false]);
        $user = $this->admin(['raonslab-travel_lab.catalog.read', 'raonslab-travel_lab.catalog.update']);
        $token = $user->createToken('travel-admin-tests')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
        $this->getJson(self::BASE.'/admin/catalog')->assertOk()->assertJsonPath('data.data.0.id', $travel->product_id);
        $this->getJson(self::BASE.'/admin/catalog/'.$travel->product_id.'/departures')->assertOk()->assertJsonPath('data.0.id', $departure->id);
        $payload = ['product_option_id' => $departure->product_option_id, 'departure_date' => '2026-11-01', 'return_date' => '2026-11-03', 'capacity' => 15, 'is_active' => true];
        $uri = self::BASE.'/admin/catalog/'.$travel->product_id.'/departures/'.$departure->id;
        $this->putJson($uri, $payload)->assertOk()->assertJsonPath('data.capacity', 15);
        $this->putJson($uri, [...$payload, 'reserved' => 7])->assertUnprocessable()->assertJsonValidationErrors('reserved');
        $this->assertSame(0, $departure->fresh()->reserved);
    }

    /** @effects new_departure_database_defaults_hydrated */
    public function test_new_admin_departure_response_contains_zero_reserved_and_active_default(): void
    {
        [$travel, $existing] = $this->createTravel();
        $option = $existing->option->replicate();
        $option->option_code = 'HTTP-NEW-DEPARTURE';
        $option->save();
        $admin = $this->admin(['raonslab-travel_lab.catalog.update']);
        $token = $admin->createToken('travel-new-departure')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(self::BASE.'/admin/catalog/'.$travel->product_id.'/departures', [
                'product_option_id' => $option->id, 'departure_date' => '2026-12-01',
                'return_date' => '2026-12-03', 'capacity' => 10,
            ])->assertOk()->assertJsonPath('data.product_option_id', $option->id)
            ->assertJsonPath('data.reserved', 0)->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.available', 10);
    }

    /** @effects departure_http_conflict_409 */
    public function test_admin_departure_http_conflicts_return_409_and_preserve_existing_row(): void
    {
        [$travel, $departure] = $this->createTravel([], [], ['reserved' => 3]);
        $user = $this->admin(['raonslab-travel_lab.catalog.update']);
        $token = $user->createToken('travel-conflict-tests')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
        $base = ['product_option_id' => $departure->product_option_id, 'departure_date' => '2026-11-01', 'return_date' => '2026-11-03', 'capacity' => 20];
        $uri = self::BASE.'/admin/catalog/'.$travel->product_id.'/departures/'.$departure->id;
        foreach ([['capacity' => 21], ['capacity' => 2], ['departure_date' => '2026-11-02']] as $change) {
            $this->putJson($uri, array_replace($base, $change))->assertStatus(409)->assertJsonPath('success', false);
            $this->assertSame(20, $departure->fresh()->capacity);
            $this->assertSame('2026-11-01', $departure->fresh()->departure_date->toDateString());
            $this->assertSame(3, $departure->fresh()->reserved);
        }
    }

    /** @effects admin_read_permission_does_not_grant_update */
    public function test_read_only_admin_cannot_update_departure(): void
    {
        [$travel, $departure] = $this->createTravel();
        $user = $this->admin(['raonslab-travel_lab.catalog.read']);
        $token = $user->createToken('travel-read-only')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson(self::BASE.'/admin/catalog')->assertOk();
        $this->putJson(self::BASE.'/admin/catalog/'.$travel->product_id.'/departures/'.$departure->id, [
            'product_option_id' => $departure->product_option_id, 'departure_date' => '2026-11-01', 'return_date' => '2026-11-03', 'capacity' => 15,
        ])->assertForbidden();
        $this->assertSame(20, $departure->fresh()->capacity);
    }

    /** @effects admin_bearer_required, admin_permission_required */
    public function test_admin_requires_real_sanctum_bearer_and_admin_permissions(): void
    {
        $this->getJson(self::BASE.'/admin/catalog')->assertUnauthorized();
        $user = User::create(['name' => 'Synthetic traveler', 'email' => 'ordinary@example.test', 'password' => 'password']);
        $token = $user->createToken('travel-tests')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson(self::BASE.'/admin/catalog')->assertForbidden();
    }
}
