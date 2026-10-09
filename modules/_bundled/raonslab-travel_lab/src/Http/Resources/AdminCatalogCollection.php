<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use Illuminate\Http\Request;

class AdminCatalogCollection extends CatalogCollection
{
    public function toArray(Request $request): array
    {
        return ['data' => AdminCatalogResource::collection($this->collection)->resolve($request), ...$this->paginationMeta()];
    }
}
