<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use Illuminate\Http\Request;

class AdminCatalogCollection extends CatalogCollection
{
    protected function abilityMap(): array
    {
        return ['can_update' => 'raonslab-travel_lab.catalog.update'];
    }

    public function toArray(Request $request): array
    {
        return ['data' => AdminCatalogResource::collection($this->collection)->resolve($request), ...$this->paginationMeta(), 'abilities' => $this->resolveCollectionAbilities($request)];
    }
}
