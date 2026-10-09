<?php

namespace Modules\Raonslab\TravelLab\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use App\Seo\Contracts\SeoCacheManagerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Raonslab\TravelLab\Support\CampaignRegistry;
use Throwable;

/**
 * 캠페인 Page 변경 시 여행 템플릿 화면의 봇 화면 캐시를 무효화합니다.
 *
 * native SeoPageCacheListener 는 `/page/{slug}`·`page/show`·`home` 만 비운다. 여행 템플릿의
 * 홈(`travel/home`)·캠페인 목록·상세·`/page/:slug` 별칭 레이아웃은 이름이 달라 남는다 —
 * 그래서 이 리스너가 레지스트리 두 slug 에 한해서만 그 화면을 비운다. 별도 업무 캐시는 없다.
 * 사이트맵·활동 로그는 native 리스너가 그대로 처리한다.
 */
class InvalidateTravelCampaignSeoCache implements HookListenerInterface
{
    /** 캠페인 내용이 그려지는 여행 템플릿 레이아웃 */
    public const LAYOUTS = ['travel/home', 'travel/campaigns', 'travel/campaign_detail'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getSubscribedHooks(): array
    {
        $hooks = [];
        foreach (['after_create', 'after_update', 'after_publish', 'after_restore', 'after_delete'] as $event) {
            // 같은 요청 안에서 즉시 비운다 — 큐 지연 동안 초안/삭제 콘텐츠가 캐시로 남지 않게.
            $hooks['sirsoft-page.page.'.$event] = ['method' => 'onPageChange', 'priority' => 30, 'sync' => true];
        }

        return $hooks;
    }

    public function __construct(
        private CampaignRegistry $registry,
    ) {}

    public function handle(...$args): void
    {
        // 명시 메서드가 처리합니다.
    }

    /**
     * native 훅 인자: create(Page, data) · update(Page, data, snapshot) · publish(Page, bool) ·
     * restore(Page, PageVersion) · delete(Page). slug 가 바뀐 수정은 이전 slug 도 확인한다.
     */
    public function onPageChange(...$args): void
    {
        $slugs = $this->campaignSlugs($args);
        if ($slugs === []) {
            return;
        }

        try {
            $cache = app(SeoCacheManagerInterface::class);
            foreach ($slugs as $slug) {
                $cache->invalidateByUrl("*/travel/campaigns/{$slug}");
                $cache->invalidateByUrl("*/page/{$slug}");
            }
            $cache->invalidateByUrl('*/travel/campaigns');
            foreach (self::LAYOUTS as $layout) {
                $cache->invalidateByLayout($layout);
            }
        } catch (Throwable $e) {
            // 캐시는 성능 장치다 — 실패해도 Page 저장은 계속하되 흔적을 남긴다.
            Log::error('[travel-lab] campaign SEO cache invalidation failed', [
                'slugs' => $slugs,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 훅 인자에서 레지스트리에 등록된 slug 만 고릅니다.
     *
     * @param  array<int, mixed>  $args
     * @return list<string>
     */
    public function campaignSlugs(array $args): array
    {
        $candidates = [];
        $page = $args[0] ?? null;
        if (is_object($page) && isset($page->slug)) {
            $candidates[] = (string) $page->slug;
        }
        $snapshot = $args[2] ?? null;
        if (is_array($snapshot) && isset($snapshot['slug'])) {
            $candidates[] = (string) $snapshot['slug'];
        }

        return array_values(array_unique(array_filter($candidates, fn (string $slug): bool => $this->registry->has($slug))));
    }
}
