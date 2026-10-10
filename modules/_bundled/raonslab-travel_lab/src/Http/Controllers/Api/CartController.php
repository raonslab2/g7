<?php

namespace Modules\Raonslab\TravelLab\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\AuthBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\AddTravelCartRequest;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\UpdateTravelCartRequest;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\WorkflowRequest;
use Modules\Raonslab\TravelLab\Http\Resources\TravelCartResource;
use Modules\Raonslab\TravelLab\Services\TravelCartService;

class CartController extends AuthBaseController
{
    public function __construct(private TravelCartService $service)
    {
        parent::__construct();
    }

    public function index(WorkflowRequest $request): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new TravelCartResource($this->service->get((int) $request->user()->id)));
    }

    public function store(AddTravelCartRequest $request): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new TravelCartResource($this->service->add(
            (int) $request->user()->id, (int) $request->validated('departure_id'), (int) $request->validated('quantity')
        )), statusCode: 201);
    }

    public function update(UpdateTravelCartRequest $request, int $cart): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new TravelCartResource($this->service->update(
            (int) $request->user()->id, $cart, (int) $request->validated('quantity')
        )));
    }

    public function destroy(WorkflowRequest $request, int $cart): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new TravelCartResource($this->service->remove((int) $request->user()->id, $cart)));
    }
}
