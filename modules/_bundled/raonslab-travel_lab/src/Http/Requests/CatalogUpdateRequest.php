<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region' => ['sometimes', Rule::in(config('raonslab-travel_lab.catalog.regions', []))],
            'theme' => ['sometimes', Rule::in(config('raonslab-travel_lab.catalog.themes', []))],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'summary' => ['sometimes', 'array:ko,en'],
            'summary.ko' => ['required_with:summary', 'string', 'max:2000'],
            'summary.en' => ['required_with:summary', 'string', 'max:2000'],
            'itinerary' => ['sometimes', 'array', 'max:365'],
            'itinerary.*' => ['array:day,title,description'],
            'itinerary.*.day' => ['required', 'integer', 'min:1', 'max:365'],
            'itinerary.*.title' => ['required', 'array:ko,en'],
            'itinerary.*.title.ko' => ['required', 'string', 'max:200'],
            'itinerary.*.title.en' => ['required', 'string', 'max:200'],
            'itinerary.*.description' => ['sometimes', 'array:ko,en'],
            'itinerary.*.description.*' => ['string', 'max:2000'],
            'published' => ['sometimes', 'boolean'],
            'product_id' => ['prohibited'],
        ];
    }
}
