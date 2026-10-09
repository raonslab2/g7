<?php

namespace Modules\Raonslab\TravelLab\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Raonslab\TravelLab\Exceptions\CatalogConflictException;
use Modules\Raonslab\TravelLab\Http\Requests\CatalogListRequest;
use Modules\Raonslab\TravelLab\Http\Requests\CatalogUpdateRequest;
use Modules\Raonslab\TravelLab\Http\Requests\DepartureRequest;
use Modules\Raonslab\TravelLab\Http\Resources\AdminCatalogCollection;
use Modules\Raonslab\TravelLab\Http\Resources\AdminCatalogResource;
use Modules\Raonslab\TravelLab\Http\Resources\AdminDepartureResource;
use Modules\Raonslab\TravelLab\Services\CatalogService;

class AdminCatalogController extends AdminBaseController
{
    public function __construct(private readonly CatalogService $catalog)
    {
        parent::__construct();
    }

    public function index(CatalogListRequest $request): JsonResponse
    {
        return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_loaded', (new AdminCatalogCollection($this->catalog->index($request->validated(), false)))->toArray($request));
    }

    public function update(CatalogUpdateRequest $request, int $product): JsonResponse
    {
        try {
            return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_saved', (new AdminCatalogResource($this->catalog->updateMetadata($product, $request->validated())))->resolve($request));
        } catch (ModelNotFoundException) {
            return ResponseHelper::moduleError('raonslab-travel_lab', 'messages.not_found', 404);
        }
    }

    public function departures(Request $request, int $product): JsonResponse
    {
        try {
            return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_loaded', AdminDepartureResource::collection($this->catalog->departures($product, false))->resolve($request));
        } catch (ModelNotFoundException) {
            return ResponseHelper::moduleError('raonslab-travel_lab', 'messages.not_found', 404);
        }
    }

    public function storeDeparture(DepartureRequest $request, int $product): JsonResponse
    {
        return $this->persistDeparture($request, $product);
    }

    public function updateDeparture(DepartureRequest $request, int $product, int $departure): JsonResponse
    {
        return $this->persistDeparture($request, $product, $departure);
    }

    private function persistDeparture(DepartureRequest $request, int $product, ?int $departure = null): JsonResponse
    {
        try {
            $saved = $this->catalog->saveDeparture($product, $request->validated(), $departure);

            return ResponseHelper::moduleSuccess('raonslab-travel_lab', 'messages.catalog_saved', (new AdminDepartureResource($saved))->resolve($request));
        } catch (ModelNotFoundException) {
            return ResponseHelper::moduleError('raonslab-travel_lab', 'messages.not_found', 404);
        } catch (CatalogConflictException $exception) {
            return ResponseHelper::moduleError('raonslab-travel_lab', $exception->getMessageKey(), 409);
        }
    }
}
