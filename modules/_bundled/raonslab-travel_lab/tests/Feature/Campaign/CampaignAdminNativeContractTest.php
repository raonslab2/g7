<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature\Campaign;

use Modules\Raonslab\TravelLab\Tests\CampaignTestCase;

/**
 * 여행 캠페인 관리자 어댑터가 의존하는 native Page 관리자 API 계약 (실제 라우트·미들웨어·권한 행).
 *
 * 어댑터 화면은 새 쓰기 API 를 만들지 않는다. 이 테스트는 그 화면이 보내는 정확한 쿼리와
 * native 401/403/스코프/abilities 응답을 고정한다.
 *
 * @scenario case=admin_native_contract
 */
class CampaignAdminNativeContractTest extends CampaignTestCase
{
    private const LIST = '/api/modules/sirsoft-page/admin/pages';

    /** 어댑터 레이아웃 data_source 와 같은 파라미터 */
    private const QUERY = ['filters' => [['field' => 'slug', 'operator' => 'starts_with', 'value' => 'travel-lab-campaign-']], 'per_page' => 100];

    /** @effects guest_401, no_permission_403, catalog_permission_not_substitute */
    public function test_guest_and_non_page_admin_are_rejected(): void
    {
        $this->getJson(self::LIST.'?'.http_build_query(self::QUERY))->assertUnauthorized();
        $catalogAdmin = $this->pageAdmin(['raonslab-travel_lab.catalog.read', 'raonslab-travel_lab.catalog.update']);
        $this->getJson(self::LIST.'?'.http_build_query(self::QUERY), ['Authorization' => $this->bearer($catalogAdmin)])->assertForbidden();
    }

    /** @effects filter_syntax_valid, substring_match_requires_exact_client_filter, abilities_present */
    public function test_adapter_query_is_valid_and_native_slug_match_is_substring_so_client_maps_exact_slugs(): void
    {
        $admin = $this->pageAdmin();
        $this->nativeCreate($admin, self::AUTUMN, ['published' => false]);
        $this->nativeCreate($admin, 'x-travel-lab-campaign-decoy');
        $this->nativeCreate($admin, 'about');

        $response = $this->getJson(self::LIST.'?'.http_build_query(self::QUERY), ['Authorization' => $this->bearer($admin)])->assertOk();
        $slugs = array_column($response->json('data.data'), 'slug');
        sort($slugs);
        // native 는 operator 를 무시하고 LIKE %value% 로 찾는다 — 어댑터는 레지스트리 slug 와 정확 일치만 표시한다.
        $this->assertSame([self::AUTUMN, 'x-travel-lab-campaign-decoy'], $slugs);
        $row = collect($response->json('data.data'))->firstWhere('slug', self::AUTUMN);
        $this->assertSame(['id', 'slug', 'title', 'published', 'published_at', 'current_version'], array_slice(array_keys($row), 0, 6));
        $this->assertFalse($row['published']);
        $this->assertTrue($row['abilities']['can_update']);
        $this->assertSame(1, $response->json('data.meta.current_page'));

        $this->getJson(self::LIST.'?filters[0][field]=slug&filters[0][operator]=prefix&filters[0][value]=x', ['Authorization' => $this->bearer($admin)])->assertStatus(422);
    }

    /** @effects read_only_admin_cannot_update, scoped_admin_cannot_see_or_edit_foreign */
    public function test_read_only_and_self_scoped_admins_get_native_denials(): void
    {
        $owner = $this->pageAdmin();
        $page = $this->nativeCreate($owner, self::AUTUMN);

        $reader = $this->pageAdmin(['sirsoft-page.pages.read']);
        $row = $this->getJson(self::LIST.'?'.http_build_query(self::QUERY), ['Authorization' => $this->bearer($reader)])->assertOk()->json('data.data.0');
        $this->assertFalse($row['abilities']['can_update']);
        $this->app['auth']->forgetGuards();
        $this->patchJson(self::LIST.'/'.$page->id.'/publish', ['published' => false], ['Authorization' => $this->bearer($reader)])->assertForbidden();
        $this->app['auth']->forgetGuards();

        $scoped = $this->pageAdmin(['sirsoft-page.pages.read', 'sirsoft-page.pages.update'], 'self');
        $this->getJson(self::LIST.'?'.http_build_query(self::QUERY), ['Authorization' => $this->bearer($scoped)])->assertOk()->assertJsonCount(0, 'data.data');
        $this->app['auth']->forgetGuards();
        $this->getJson(self::LIST.'/'.$page->id, ['Authorization' => $this->bearer($scoped)])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->patchJson(self::LIST.'/'.$page->id.'/publish', ['published' => false], ['Authorization' => $this->bearer($scoped)])->assertForbidden();
        $this->assertTrue($page->fresh()->published);
        $this->getJson(self::BASE.'/campaigns/'.self::AUTUMN)->assertOk();
    }
}
