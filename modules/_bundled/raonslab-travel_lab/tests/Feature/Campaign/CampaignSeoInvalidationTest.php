<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature\Campaign;

use App\Seo\Contracts\SeoCacheManagerInterface;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Listeners\InvalidateTravelCampaignSeoCache;
use Modules\Raonslab\TravelLab\Tests\CampaignTestCase;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * native Page 훅 → 여행 캠페인 화면 SEO 캐시 무효화.
 *
 * 훅 발화는 실제 PageService 이고, SEO 캐시 저장소만 호출 기록용 대역이다(외부 상태 없음).
 *
 * @scenario case=seo_invalidation
 */
class CampaignSeoInvalidationTest extends CampaignTestCase
{
    /** @var array{urls: list<string>, layouts: list<string>} */
    private array $calls = ['urls' => [], 'layouts' => []];

    protected function setUp(): void
    {
        parent::setUp();
        $calls = &$this->calls;
        $this->app->instance(SeoCacheManagerInterface::class, new class($calls) implements SeoCacheManagerInterface
        {
            public function __construct(private array &$calls) {}

            public function get(string $url, string $locale): ?string
            {
                return null;
            }

            public function put(string $url, string $locale, string $html): void {}

            public function putWithLayout(string $url, string $locale, string $html, string $layoutName): void {}

            public function invalidateByUrl(string $urlPattern): int
            {
                $this->calls['urls'][] = $urlPattern;

                return 0;
            }

            public function invalidateByLayout(string $layoutName): int
            {
                $this->calls['layouts'][] = $layoutName;

                return 0;
            }

            public function clearAll(): void {}

            public function getCachedUrls(): array
            {
                return [];
            }
        });
    }

    private function reset(): void
    {
        $this->calls = ['urls' => [], 'layouts' => []];
    }

    private function assertTravelInvalidated(string $slug): void
    {
        $this->assertContains('*/travel/campaigns/'.$slug, $this->calls['urls']);
        $this->assertContains('*/travel/campaigns', $this->calls['urls']);
        foreach (InvalidateTravelCampaignSeoCache::LAYOUTS as $layout) {
            $this->assertContains($layout, $this->calls['layouts']);
        }
    }

    /** @effects seo_invalidated_on_create_update_publish_restore_delete */
    public function test_every_native_campaign_lifecycle_hook_invalidates_travel_screens(): void
    {
        $admin = $this->pageAdmin([...['sirsoft-page.pages.read', 'sirsoft-page.pages.create', 'sirsoft-page.pages.update', 'sirsoft-page.pages.delete']]);
        $service = app(PageService::class);

        $page = $this->nativeCreate($admin, self::AUTUMN);
        $this->assertTravelInvalidated(self::AUTUMN);
        $this->assertContains('page/show', $this->calls['layouts'], 'native listener still runs');

        foreach ([
            fn ($p) => $service->updatePage($p, ['title' => ['ko' => '수정', 'en' => 'Edited']]),
            fn ($p) => $service->changePublishStatus($p, false),
            fn ($p) => $service->restoreVersion($p, (int) DB::table('page_versions')->where('page_id', $p->id)->where('version', 1)->value('id')),
            fn ($p) => tap($p, fn () => $service->deletePage($p)),
        ] as $mutation) {
            $this->reset();
            $page = $this->asActor($admin, fn () => $mutation($page->fresh() ?? $page));
            $this->assertTravelInvalidated(self::AUTUMN);
        }
    }

    /** @effects seo_ignores_unrelated_pages, seo_old_slug_on_rename */
    public function test_unrelated_pages_do_not_touch_travel_screens_but_slug_rename_away_does(): void
    {
        $admin = $this->pageAdmin();
        $this->nativeCreate($admin, 'about');
        $this->assertSame([], array_values(array_intersect(InvalidateTravelCampaignSeoCache::LAYOUTS, $this->calls['layouts'])));
        $this->assertSame([], array_values(array_filter($this->calls['urls'], fn ($u) => str_contains($u, 'travel'))));

        $page = $this->nativeCreate($admin, self::WEEKEND);
        $this->reset();
        $this->asActor($admin, fn () => app(PageService::class)->updatePage($page, ['slug' => 'weekend-moved']));
        $this->assertTravelInvalidated(self::WEEKEND);
    }
}
