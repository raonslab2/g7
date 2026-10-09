<?php

namespace Modules\Raonslab\TravelLab\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\PublicBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Raonslab\TravelLab\Http\Requests\CatalogListRequest;
use Modules\Raonslab\TravelLab\Http\Resources\CatalogCollection;
use Modules\Raonslab\TravelLab\Http\Resources\CatalogResource;
use Modules\Raonslab\TravelLab\Http\Resources\DepartureResource;
use Modules\Raonslab\TravelLab\Services\CatalogService;

class CatalogController extends PublicBaseController
{
    public function __construct(private readonly CatalogService $catalog)
    {
        parent::__construct();
    }

    /** 공개 여행 목록을 data.data 배열과 pagination 메타로 반환합니다. */
    public function index(CatalogListRequest $request): JsonResponse
    {
        return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_loaded', (new CatalogCollection($this->catalog->index($request->validated())))->toArray($request));
    }

    /** product는 travel 메타 ID가 아닌 이커머스 상품 ID입니다. */
    public function show(Request $request, int $product): JsonResponse
    {
        try {
            return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_loaded', (new CatalogResource($this->catalog->find($product)))->resolve($request));
        } catch (ModelNotFoundException) {
            return ResponseHelper::moduleError('raonslab-travel_lab', 'messages.not_found', 404);
        }
    }

    public function departures(Request $request, int $product): JsonResponse
    {
        try {
            return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_loaded', DepartureResource::collection($this->catalog->departures($product))->resolve($request));
        } catch (ModelNotFoundException) {
            return ResponseHelper::moduleError('raonslab-travel_lab', 'messages.not_found', 404);
        }
    }

    public function facets(): JsonResponse
    {
        return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_loaded', $this->catalog->facets());
    }
}
