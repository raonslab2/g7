<?php

namespace Modules\Raonslab\TravelLab\Http\Controllers\Api;

use App\Http\Controllers\Api\Base\PublicBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\TravelLab\Http\Requests\Campaign\CampaignListRequest;
use Modules\Raonslab\TravelLab\Http\Requests\Campaign\CampaignShowRequest;
use Modules\Raonslab\TravelLab\Http\Resources\CampaignResource;
use Modules\Raonslab\TravelLab\Services\CampaignService;

/**
 * 여행 캠페인 공개 API (native Page 기반 고정 두 슬롯).
 *
 * 선택적 인증(TravelOptionalSanctum)은 사용자별 제한 계산에만 쓰이고, 발행 판정에는 쓰이지 않는다.
 * 관리자도 이 경로에서는 초안을 볼 수 없다 — 미발행·부재·미등록은 모두 같은 404 다.
 */
class CampaignController extends PublicBaseController
{
    public function __construct(
        private CampaignService $campaignService,
    ) {
        parent::__construct();
    }

    /**
     * 발행된 캠페인 슬롯 목록 (레지스트리 순서, 최대 두 건).
     */
    public function index(CampaignListRequest $request): JsonResponse
    {
        $items = array_map(
            fn (array $item): array => CampaignResource::forSlot($item['page'], $item['slot'])->toListArray($request),
            $this->campaignService->listPublished(),
        );

        return $this->success('raonslab-travel_lab::campaigns.messages.list_loaded', ['items' => $items]);
    }

    /**
     * 발행된 캠페인 상세 (본문 포함).
     */
    public function show(CampaignShowRequest $request, string $slug): JsonResponse
    {
        $item = $this->campaignService->findPublished($request->slug());
        if ($item === null) {
            return $this->notFound('raonslab-travel_lab::campaigns.errors.not_found');
        }

        return $this->success(
            'raonslab-travel_lab::campaigns.messages.loaded',
            CampaignResource::forSlot($item['page'], $item['slot'])->toArray($request),
        );
    }
}
