<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class CatalogResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        $summary = $this->summary ?? [];

        return [
            'id' => $this->product_id,
            'title' => $this->product->getLocalizedName(),
            'region' => $this->region,
            'theme' => $this->theme,
            'duration_days' => $this->duration_days,
            'summary' => $summary[app()->getLocale()] ?? $summary[config('app.fallback_locale', 'ko')] ?? reset($summary) ?: '',
            'itinerary' => $this->itinerary ?? [],
            'from_price' => $this->departures->min(fn ($departure) => $departure->option->getSellingPrice()),
            'currency_code' => $this->product->currency_code,
            'image_url' => $this->product->getThumbnailUrl(),
            'departures' => DepartureResource::collection($this->departures)->resolve($request),
        ];
    }
}
