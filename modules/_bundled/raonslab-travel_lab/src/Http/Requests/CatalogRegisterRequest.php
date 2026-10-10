<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Sirsoft\Ecommerce\Models\Product;

class CatalogRegisterRequest extends CatalogUpdateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        foreach (['region', 'theme', 'duration_days', 'summary'] as $key) {
            $rules[$key] = ['required', ...array_values(array_filter($rules[$key], fn ($rule) => $rule !== 'sometimes'))];
        }
        $rules['product_id'] = ['required', 'integer', Rule::exists(Product::class, 'id')->whereNull('deleted_at')];

        return $rules;
    }
}
