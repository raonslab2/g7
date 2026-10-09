<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiCollection;
use Illuminate\Http\Request;

class InquiryCollection extends BaseApiCollection
{
    public $collects = InquiryResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn (InquiryResource $resource) => $resource->resolve($request))->all(),
            ...$this->paginationMeta(),
        ];
    }
}
