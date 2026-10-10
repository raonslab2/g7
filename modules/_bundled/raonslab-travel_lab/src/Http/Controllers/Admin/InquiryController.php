<?php

namespace Modules\Raonslab\TravelLab\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\ListInquiriesRequest;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\UpdateInquiryRequest;
use Modules\Raonslab\TravelLab\Http\Requests\Workflow\WorkflowRequest;
use Modules\Raonslab\TravelLab\Http\Resources\AdminInquiryCollection;
use Modules\Raonslab\TravelLab\Http\Resources\AdminInquiryResource;
use Modules\Raonslab\TravelLab\Services\InquiryService;

class InquiryController extends AdminBaseController
{
    public function __construct(private InquiryService $service)
    {
        parent::__construct();
    }

    public function index(ListInquiriesRequest $request): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new AdminInquiryCollection($this->service->listAdmin(
            (int) $request->user()->id, (int) $request->validated('per_page', 20), (int) $request->validated('page', 1),
            $request->validated('status') === null ? null : InquiryStatus::from($request->validated('status'))
        )));
    }

    public function show(WorkflowRequest $request, int $inquiry): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new AdminInquiryResource($this->service->findAdmin((int) $request->user()->id, $inquiry)));
    }

    public function update(UpdateInquiryRequest $request, int $inquiry): JsonResponse
    {
        return ResponseHelper::successWithResource(resource: new AdminInquiryResource($this->service->transition(
            (int) $request->user()->id, $inquiry, InquiryStatus::from($request->validated('status')), $request->validated('admin_note'), $request->exists('admin_note')
        )));
    }
}
