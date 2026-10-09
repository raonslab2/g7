<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;

class DepartureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 인증과 권한은 라우트 미들웨어가 판정합니다.
    }

    public function rules(): array
    {
        return [
            'product_option_id' => ['required', 'integer', Rule::exists(ProductOption::class, 'id')->where('product_id', (int) $this->route('product'))],
            'departure_date' => ['required', 'date_format:Y-m-d'],
            'return_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:departure_date'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
            'reserved' => ['prohibited'],
            'product_id' => ['prohibited'],
            'unit_price' => ['prohibited'],
        ];
    }
}
