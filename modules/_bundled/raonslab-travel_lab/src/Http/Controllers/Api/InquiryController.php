<?php

namespace Modules\Raonslab\TravelLab\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\AuthBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\ListInquiriesRequest;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\SubmitInquiryRequest;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\WorkflowRequest;
use Modules\Raonslab\TravelLab\Http\Resources\InquiryCollection;
use Modules\Raonslab\TravelLab\Http\Resources\InquiryResource;
use Modules\Raonslab\TravelLab\Services\InquiryService;

class InquiryController extends AuthBaseController
{
    public function __construct(private InquiryService $service)
    {
        parent::__construct();
    }

    public function index(ListInquiriesRequest $request): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new InquiryCollection($this->service->listOwn(
            (int) $request->user()->id, (int) $request->validated('per_page', 20), (int) $request->validated('page', 1),
            $request->validated('status') === null ? null : InquiryStatus::from($request->validated('status'))
        )));
    }

    public function store(SubmitInquiryRequest $request): JsonResponse
    {
        $inquiry = $this->service->submit(
            (int) $request->user()->id, $request->validated('cart_ids'), $request->validated('contact'), $request->validated('idempotency_key')
        );

        return ResponseHelper::successWithResource(resource: new InquiryResource($inquiry), statusCode: $inquiry->wasRecentlyCreated ? 201 : 200);
    }

    public function show(WorkflowRequest $request, int $inquiry): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new InquiryResource($this->service->findOwn((int) $request->user()->id, $inquiry)));
    }

    public function cancel(WorkflowRequest $request, int $inquiry): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new InquiryResource($this->service->cancel((int) $request->user()->id, $inquiry)));
    }
}
