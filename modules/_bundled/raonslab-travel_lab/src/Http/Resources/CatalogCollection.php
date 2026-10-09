<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiCollection;
use Illuminate\Http\Request;

class CatalogCollection extends BaseApiCollection
{
    public function toArray(Request $request): array
    {
        return ['data' => CatalogResource::collection($this->collection)->resolve($request), ...$this->paginationMeta()];
    }
}
