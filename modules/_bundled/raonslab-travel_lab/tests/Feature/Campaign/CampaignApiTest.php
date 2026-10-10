<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature\Campaign;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelOptionalSanctum;
use Modules\Raonslab\TravelLab\Http\Resources\CampaignResource;
use Modules\Raonslab\TravelLab\Tests\CampaignTestCase;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * 캠페인 공개 API — 실제 native PageService/Repository/버전/훅, isolated SQLite.
 *
 * @scenario case=public_api
 */
class CampaignApiTest extends CampaignTestCase
{
    private const ITEM_KEYS = ['slug', 'kind', 'theme', 'title', 'excerpt', 'current_version', 'published_at', 'path', 'catalog_query', 'art_variant'];

    /** @effects empty_list_without_fallback, named_routes */
    public function test_list_is_empty_until_native_pages_exist_and_routes_are_named(): void
    {
        $this->getJson(self::BASE.'/campaigns')->assertOk()->assertJsonPath('success', true)->assertExactJson([
            'success' => true, 'message' => __('raonslab-travel_lab::campaigns.messages.list_loaded'), 'data' => ['items' => []],
        ]);
        $this->assertTrue(Route::has('api.modules.raonslab-travel_lab.campaigns.index'));
        $this->assertTrue(Route::has('api.modules.raonslab-travel_lab.campaigns.show'));
    }

    /** @effects registry_order, published_only, exact_projection_fields */
    public function test_list_returns_published_registry_slots_in_registry_order_with_exact_fields(): void
    {
        $admin = $this->pageAdmin();
        // 생성 순서를 레지스트리와 반대로 — 응답 순서는 레지스트리가 정한다.
        $this->nativeCreate($admin, self::WEEKEND, ['title' => ['ko' => '주말 리셋', 'en' => 'Weekend reset']]);
        $this->nativeCreate($admin, self::AUTUMN);

        $items = $this->getJson(self::BASE.'/campaigns')->assertOk()->json('data.items');
        $this->assertSame([self::AUTUMN, self::WEEKEND], array_column($items, 'slug'));
        foreach ($items as $item) {
            $this->assertSame(self::ITEM_KEYS, array_keys($item));
            $this->assertSame('campaign', $item['kind']);
        }
        $this->assertSame(['theme' => 'nature', 'sort' => 'recommended'], $items[0]['catalog_query']);
        $this->assertSame(['theme' => 'wellness', 'sort' => 'recommended'], $items[1]['catalog_query']);
        $this->assertSame('/travel/campaigns/'.self::WEEKEND, $items[1]['path']);
        $this->assertSame(['forest', 'lake'], array_column($items, 'art_variant'));
    }

    /** @effects draft_hidden_for_all_actors, preview_never_projected */
    public function test_draft_slot_is_absent_and_404_for_guest_member_and_page_admin(): void
    {
        $admin = $this->pageAdmin();
        $this->nativeCreate($admin, self::AUTUMN, ['published' => false]);
        $this->nativeCreate($admin, self::WEEKEND);

        foreach ([null, $this->member(), $admin] as $viewer) {
            $headers = $viewer ? ['Authorization' => $this->bearer($viewer)] : [];
            $this->getJson(self::BASE.'/campaigns', $headers)->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.slug', self::WEEKEND);
            $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN, $headers)->assertNotFound()->assertJsonPath('success', false)
                ->assertJsonPath('message', __('raonslab-travel_lab::campaigns.errors.not_found'));
            $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN.'?preview=1', $headers)->assertStatus(422);
            $this->app['auth']->forgetGuards();
        }
        // native 공개 API 는 같은 관리자에게 미리보기를 허용한다 — 여행 경로만 막는다는 대비.
        $this->getJson('/api/modules/sirsoft-page/pages/'.self::AUTUMN, ['Authorization' => $this->bearer($admin)])->assertOk()->assertJsonPath('data.is_preview', true);
    }

    /** @effects unregistered_slug_404, no_arbitrary_selector */
    public function test_unregistered_or_foreign_published_pages_are_never_exposed(): void
    {
        $admin = $this->pageAdmin();
        $this->nativeCreate($admin, 'travel-lab-campaign-other', ['title' => ['ko' => '등록 안 됨', 'en' => 'Unregistered']]);
        $this->nativeCreate($admin, 'about', ['title' => ['ko' => '회사 소개', 'en' => 'About']]);

        $this->getJson(self::BASE.'/campaigns')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson(self::BASE.'/campaigns/travel-lab-campaign-other')->assertNotFound();
        $this->getJson(self::BASE.'/campaigns/about')->assertNotFound();
        $this->getJson(self::BASE.'/campaigns/TRAVEL-LAB-CAMPAIGN-AUTUMN-ESCAPE')->assertNotFound();
        foreach (['slug=about', 'search=about', 'prefix=travel', 'ids[]=1', 'q=x'] as $query) {
            $this->getJson(self::BASE.'/campaigns?'.$query)->assertStatus(422);
        }
    }

    /** @effects detail_body_and_mode, html_excerpt_plain_text, no_admin_fields */
    public function test_detail_carries_persisted_body_and_list_carries_only_plain_excerpt(): void
    {
        $admin = $this->pageAdmin();
        $html = '<h2>숲</h2><p>가을 <strong>산책</strong>&amp;휴식</p><script>alert(1)</script><img src="https://evil.example/x.png">';
        $this->nativeCreate($admin, self::AUTUMN, ['content' => ['ko' => $html, 'en' => $html], 'content_mode' => 'html', 'seo_meta' => ['title' => 'x']]);

        $item = $this->getJson(self::BASE.'/campaigns')->json('data.items.0');
        $this->assertSame('숲 가을 산책 &휴식', $item['excerpt']);
        $detail = $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN)->assertOk()->json('data');
        $this->assertSame([...self::ITEM_KEYS, 'content', 'content_mode', 'updated_at'], array_keys($detail));
        $this->assertSame('html', $detail['content_mode']);
        $this->assertSame($html, $detail['content'], 'Raw body is passed to the client-side formatting-only sanitizer');
        foreach (['id', 'created_by', 'updated_by', 'creator', 'attachments', 'seo_meta', 'is_preview', 'abilities', 'versions'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $detail);
        }
        $this->assertSame(str_repeat('가', 139).'…', CampaignResource::plainExcerpt(str_repeat('가', 400), 'text'));
    }

    /** @effects locale_and_native_fallback */
    public function test_locale_uses_native_fallback_rule(): void
    {
        $admin = $this->pageAdmin();
        config(['app.fallback_locale' => 'ko']);
        $this->nativeCreate($admin, self::AUTUMN, ['title' => ['ko' => '한국어 제목'], 'content' => ['ko' => '한국어 본문']]);
        $this->nativeCreate($admin, self::WEEKEND, ['title' => ['ko' => '주말', 'en' => 'Weekend'], 'content' => ['ko' => '본문', 'en' => 'Body']]);

        $items = $this->getJson(self::BASE.'/campaigns', ['Accept-Language' => 'en'])->json('data.items');
        $this->assertSame(['한국어 제목', 'Weekend'], array_column($items, 'title'));
        $this->assertSame('한국어 본문', $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN, ['Accept-Language' => 'en'])->json('data.content'));
        $this->assertSame('본문', $this->getJson(self::BASE.'/campaigns/'.self::WEEKEND, ['Accept-Language' => 'ko'])->json('data.content'));
        $this->assertSame('Body', $this->getJson(self::BASE.'/campaigns/'.self::WEEKEND, ['Accept-Language' => 'en'])->json('data.content'));
    }

    /** @effects unpublish_removes, republish_returns, edit_new_version, restore_new_version */
    public function test_native_edit_unpublish_restore_transitions_are_reflected_without_cache(): void
    {
        $admin = $this->pageAdmin();
        $page = $this->nativeCreate($admin, self::AUTUMN);
        $service = app(PageService::class);

        $page = $this->asActor($admin, fn () => $service->updatePage($page, ['title' => ['ko' => '수정된 제목', 'en' => 'Edited title'], 'content' => ['ko' => '새 본문', 'en' => 'New body']]));
        $this->assertSame(2, $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN)->json('data.current_version'));
        $this->assertSame('New body', $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN)->json('data.content'));

        $page = $this->asActor($admin, fn () => $service->changePublishStatus($page, false));
        $this->getJson(self::BASE.'/campaigns')->assertJsonCount(0, 'data.items');
        $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN)->assertNotFound();

        $version1 = DB::table('page_versions')->where('page_id', $page->id)->where('version', 1)->value('id');
        $page = $this->asActor($admin, fn () => $service->restoreVersion($page, (int) $version1));
        $this->assertSame(3, $page->current_version);
        $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN)->assertNotFound(); // 복원은 발행 상태를 바꾸지 않는다

        $this->asActor($admin, fn () => $service->changePublishStatus($page->fresh(), true));
        $detail = $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN)->assertOk()->json('data');
        $this->assertSame(3, $detail['current_version']);
        $this->assertSame("First line\nSecond line", $detail['content']);
        $this->assertSame(3, DB::table('page_versions')->where('page_id', $page->id)->count());
        $this->assertGreaterThanOrEqual(4, DB::table('activity_logs')->count(), 'native activity listener ran for create/update/publish/restore');
    }

    /** @effects optional_auth_before_campaign_bucket, separate_throttle_prefix */
    public function test_routes_use_optional_auth_before_dedicated_atomic_bucket(): void
    {
        foreach (['index', 'show'] as $name) {
            $middleware = Route::getRoutes()->getByName('api.modules.raonslab-travel_lab.campaigns.'.$name)->gatherMiddleware();
            $auth = array_search(TravelOptionalSanctum::class, $middleware, true);
            $throttle = array_key_first(array_filter($middleware, fn ($m) => is_string($m) && str_contains($m, 'travel-lab-campaign-public:')));
            $this->assertIsInt($auth);
            $this->assertNotNull($throttle);
            $this->assertLessThan($throttle, $auth);
            $this->assertStringContainsString(':600,1,travel-lab-campaign-public:', $middleware[$throttle]);
        }
        $this->getJson(self::BASE.'/campaigns')->assertOk()->assertHeader('X-RateLimit-Limit', '600');
    }
}
