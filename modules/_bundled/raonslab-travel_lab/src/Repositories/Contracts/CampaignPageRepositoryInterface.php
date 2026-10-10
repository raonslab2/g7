<?php

namespace Modules\Raonslab\TravelLab\Repositories\Contracts;

use Modules\Sirsoft\Page\Models\Page;

/**
 * 캠페인 고객 화면이 native sirsoft-page 를 읽는 단일 어댑터.
 *
 * 고객 조회는 언제나 native PageService::getPublishedPageBySlug($slug, false) 를 거친다.
 * 관리자 미리보기(allowUnpublished=true) 경로는 이 계약에 없다 — 고객 캠페인 화면은 관리자라도
 * 초안을 볼 수 없다. 쓰기 메서드도 없다(쓰기는 native 관리자 API/서비스 소유).
 */
interface CampaignPageRepositoryInterface
{
    /**
     * 발행된 Page 를 slug 로 조회합니다 (미발행·부재 시 null).
     *
     * @param  string  $slug  레지스트리에 등록된 Page slug
     * @return Page|null 발행 상태가 확인된 Page
     */
    public function findPublishedBySlug(string $slug): ?Page;
}
