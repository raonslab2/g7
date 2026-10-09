<?php

namespace Modules\Raonslab\TravelLab\Services;

use Modules\Raonslab\TravelLab\Repositories\Contracts\CampaignPageRepositoryInterface;
use Modules\Raonslab\TravelLab\Support\CampaignRegistry;
use Modules\Raonslab\TravelLab\Support\CampaignSlot;
use Modules\Sirsoft\Page\Models\Page;

/**
 * 고정 슬롯 캠페인 투영.
 *
 * 레지스트리 두 슬롯만 열거하고, 각 슬롯의 native Page 가 발행 상태일 때만 내보낸다.
 * 임의 slug·검색·접두사 조회는 없다. 열람자(비회원/회원/관리자)와 무관하게 판정이 같다.
 */
class CampaignService
{
    public function __construct(
        private CampaignRegistry $registry,
        private CampaignPageRepositoryInterface $pages,
    ) {}

    /**
     * 발행된 슬롯을 레지스트리 순서로 반환합니다 (초안·부재 슬롯은 빠짐).
     *
     * @return list<array{slot: CampaignSlot, page: Page}>
     */
    public function listPublished(): array
    {
        $items = [];
        foreach ($this->registry->all() as $slot) {
            $page = $this->pages->findPublishedBySlug($slot->slug);
            if ($page !== null) {
                $items[] = ['slot' => $slot, 'page' => $page];
            }
        }

        return $items;
    }

    /**
     * 레지스트리에 있고 발행된 슬롯 하나 (미등록·부재·초안은 모두 null).
     *
     * @return array{slot: CampaignSlot, page: Page}|null
     */
    public function findPublished(string $slug): ?array
    {
        $slot = $this->registry->find($slug);
        if ($slot === null) {
            // 미등록 slug 는 native Page 를 조회조차 하지 않는다.
            return null;
        }

        $page = $this->pages->findPublishedBySlug($slot->slug);

        return $page === null ? null : ['slot' => $slot, 'page' => $page];
    }
}
