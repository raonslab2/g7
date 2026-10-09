<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use App\Support\Query\PaginationLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Raonslab\TravelLab\Enums\CatalogSort;

class CatalogListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'region' => ['nullable', 'string', 'max:50'],
            'theme' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999999.99', ...($this->filled('min_price') ? ['gte:min_price'] : [])],
            'sort' => ['nullable', Rule::enum(CatalogSort::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
            'page' => ['nullable', 'integer', 'min:1', ...(PaginationLimits::maxPage('travel_lab.catalog') ? ['max:'.PaginationLimits::maxPage('travel_lab.catalog')] : [])],
        ];
    }
}
