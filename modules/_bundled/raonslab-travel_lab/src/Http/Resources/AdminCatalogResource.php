<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use Illuminate\Http\Request;

class AdminCatalogResource extends CatalogResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'published' => $this->published,
            'summary_translations' => $this->summary,
            'title_translations' => $this->product->name,
            'departures' => AdminDepartureResource::collection($this->departures)->resolve($request),
        ];
    }
}
