<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use Illuminate\Http\Request;

class AdminCatalogResource extends CatalogResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'product_code' => $this->product->product_code,
            'published' => $this->published,
            'summary_translations' => $this->summary,
            'itinerary_translations' => $this->itinerary,
            'title_translations' => $this->product->name,
            'options' => $this->product->options->map(fn ($option) => [
                'id' => $option->id,
                'option_name' => $option->getLocalizedOptionName(),
                'stock_quantity' => $option->stock_quantity,
                'is_active' => $option->is_active,
            ])->values()->all(),
            'departures' => AdminDepartureResource::collection($this->departures)->resolve($request),
        ];
    }
}
