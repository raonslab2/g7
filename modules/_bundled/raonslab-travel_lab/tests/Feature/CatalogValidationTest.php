<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Modules\Raonslab\TravelLab\Http\Requests\CatalogListRequest;
use Modules\Raonslab\TravelLab\Http\Requests\DepartureRequest;
use Modules\Raonslab\TravelLab\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CatalogValidationTest extends ModuleTestCase
{
    public static function invalidFilters(): array
    {
        return [
            'invalid-date' => [['date_from' => '2026-02-30'], 'date_from'],
            'non-iso-date' => [['date_from' => '10/11/2026'], 'date_from'],
            'reversed-date-range' => [['date_from' => '2026-11-10', 'date_to' => '2026-11-01'], 'date_to'],
            'over-limit' => [['per_page' => 49], 'per_page'],
            'zero-page' => [['page' => 0], 'page'],
            'negative-price' => [['min_price' => -1], 'min_price'],
            'reversed-price-range' => [['min_price' => 200, 'max_price' => 100], 'max_price'],
            'unsupported-sort' => [['sort' => 'unsafe_sql'], 'sort'],
        ];
    }

    /** @effects invalid_filter_rejected */
    #[DataProvider('invalidFilters')]
    public function test_catalog_form_request_rejects_invalid_dates_ranges_and_bounds(array $input, string $field): void
    {
        $request = CatalogListRequest::create('/catalog', 'GET', $input);
        $request->setContainer($this->app);
        $validator = Validator::make($input, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has($field), $validator->errors()->toJson());
    }

    /** @effects valid_filter_accepted */
    public function test_valid_filters_allow_same_day_and_one_sided_ranges(): void
    {
        foreach ([[], ['date_from' => '2026-11-01'], ['date_to' => '2026-11-01'], ['date_from' => '2026-11-01', 'date_to' => '2026-11-01', 'per_page' => 48], ['min_price' => 100], ['max_price' => 200]] as $input) {
            $request = CatalogListRequest::create('/catalog', 'GET', $input);
            $request->setContainer($this->app);
            $validator = Validator::make($input, $request->rules());
            $this->assertTrue($validator->passes(), $validator->errors()->toJson());
        }
    }

    /** @effects departure_dates_and_scope_validated */
    public function test_departure_request_rejects_impossible_and_reversed_dates(): void
    {
        [, $departure] = $this->createTravel();
        foreach ([['departure_date' => '2026-02-30'], ['return_date' => '2026-10-01']] as $invalid) {
            $input = array_replace(['product_option_id' => $departure->product_option_id, 'departure_date' => '2026-11-01', 'return_date' => '2026-11-03', 'capacity' => 20, 'is_active' => true], $invalid);
            $request = DepartureRequest::create('/catalog/1/departures', 'POST', $input);
            $request->setContainer($this->app);
            $request->setRouteResolver(fn () => null);
            $validator = Validator::make($input, $request->rules());
            $this->assertTrue($validator->fails(), 'Impossible or reversed dates accepted');
        }
    }
}
