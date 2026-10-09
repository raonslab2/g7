<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Raonslab\TravelLab\Exceptions\CatalogConflictException;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Services\CatalogService;
use Modules\Raonslab\TravelLab\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DepartureManagementTest extends ModuleTestCase
{
    /** @effects departure_created_with_commerce_option, departure_update_preserves_reservations */
    public function test_create_and_update_departure_use_product_option_and_preserve_reserved(): void
    {
        [$travel, $existing] = $this->createTravel();
        $option = $existing->option->replicate();
        $option->option_code = 'SECOND';
        $option->save();
        $service = $this->app->make(CatalogService::class);
        $created = $service->saveDeparture($travel->product_id, [
            'product_option_id' => $option->id, 'departure_date' => '2026-12-01',
            'return_date' => '2026-12-03', 'capacity' => 10,
        ]);
        $this->assertSame($travel->product_id, $created->product_id);
        $this->assertSame($option->id, $created->product_option_id);
        $this->assertSame(0, $created->reserved);
        $this->assertTrue($created->is_active);
        $created->forceFill(['reserved' => 2])->save();
        $updated = $service->saveDeparture($travel->product_id, [
            'product_option_id' => $option->id, 'departure_date' => '2026-12-01',
            'return_date' => '2026-12-03', 'capacity' => 15, 'is_active' => false,
        ], $created->id);
        $this->assertSame(2, $updated->reserved);
        $this->assertSame(15, $updated->capacity);
        $this->assertFalse($updated->is_active);
    }

    public static function conflictingChanges(): array
    {
        return [
            'capacity-exceeds-option-stock' => [[], ['capacity' => 21]],
            'capacity-below-reserved' => [['reserved' => 5], ['capacity' => 4]],
            'date-change-after-reserved' => [['reserved' => 1], ['departure_date' => '2026-11-02']],
        ];
    }

    /** @effects departure_conflict_rejected, failed_update_atomic */
    #[DataProvider('conflictingChanges')]
    public function test_stock_capacity_and_used_date_conflicts_leave_departure_unchanged(array $existing, array $change): void
    {
        [$travel, $departure] = $this->createTravel([], [], $existing);
        $before = $departure->fresh()->toArray();
        try {
            $this->app->make(CatalogService::class)->saveDeparture($travel->product_id, array_replace([
                'product_option_id' => $departure->product_option_id, 'departure_date' => '2026-11-01',
                'return_date' => '2026-11-03', 'capacity' => 20, 'is_active' => true,
            ], $change), $departure->id);
            $this->fail('Unsafe departure change accepted');
        } catch (CatalogConflictException) {
            $this->assertSame($before, $departure->fresh()->toArray());
        }
    }

    /** @effects cross_product_departure_rejected */
    public function test_option_of_another_commerce_product_cannot_be_assigned(): void
    {
        [$travel, $departure] = $this->createTravel();
        [, $other] = $this->createTravel();
        $this->expectException(ModelNotFoundException::class);
        $this->app->make(CatalogService::class)->saveDeparture($travel->product_id, [
            'product_option_id' => $other->product_option_id, 'departure_date' => '2026-11-01',
            'return_date' => '2026-11-03', 'capacity' => 20, 'is_active' => true,
        ], $departure->id);
    }

    /** @effects duplicate_option_departure_rejected */
    public function test_existing_option_cannot_be_used_by_a_second_departure(): void
    {
        [$travel, $departure] = $this->createTravel();
        $this->expectException(CatalogConflictException::class);
        try {
            $this->app->make(CatalogService::class)->saveDeparture($travel->product_id, [
                'product_option_id' => $departure->product_option_id, 'departure_date' => '2026-12-01',
                'return_date' => '2026-12-03', 'capacity' => 20, 'is_active' => true,
            ]);
        } finally {
            $this->assertSame(1, Departure::count());
        }
    }
}
