<?php

namespace Modules\Raonslab\TravelLab\Repositories;

use Modules\Raonslab\TravelLab\Repositories\Contracts\CampaignPageRepositoryInterface;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * native PageService 를 감싸는 캠페인 Page 조회 어댑터.
 *
 * Page 테이블을 직접 쿼리하지 않는다. 발행 판정은 native 서비스에 literal false 를 넘겨
 * 위임하고, 반환값의 발행 상태를 한 번 더 확인한다(서비스 동작이 바뀌어도 초안이 새지 않게).
 */
class CampaignPageRepository implements CampaignPageRepositoryInterface
{
    public function __construct(
        private PageService $pageService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function findPublishedBySlug(string $slug): ?Page
    {
        $page = $this->pageService->getPublishedPageBySlug($slug, false);

        return ($page instanceof Page && $page->published === true) ? $page : null;
    }
}
