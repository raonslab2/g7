<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature\Campaign;

use Modules\Raonslab\TravelLab\Support\CampaignRegistry;
use Modules\Raonslab\TravelLab\Tests\CampaignTestCase;

/**
 * 고정 두 슬롯 레지스트리 — 불변성과 화면 간 패리티.
 *
 * @scenario case=registry
 */
class CampaignRegistryTest extends CampaignTestCase
{
    /** @effects exactly_two_slots, registry_not_widened_by_config */
    public function test_registry_is_exactly_two_fixed_slots_and_ignores_runtime_config_overrides(): void
    {
        config(['raonslab-travel_lab.campaigns.slots' => ['x' => ['slug' => 'about', 'theme' => 'city', 'sort' => 'recommended', 'art_variant' => 'city']]]);
        $registry = new CampaignRegistry;

        $this->assertSame([self::AUTUMN, self::WEEKEND], $registry->slugs());
        $this->assertNull($registry->find('about'));
        $this->assertFalse($registry->has('travel-lab-campaign-other'));
        $this->assertSame(['theme' => 'nature', 'sort' => 'recommended'], $registry->find(self::AUTUMN)->catalogQuery());
        $this->assertSame(['theme' => 'wellness', 'sort' => 'recommended'], $registry->find(self::WEEKEND)->catalogQuery());
        foreach ($registry->slugs() as $slug) {
            $this->assertMatchesRegularExpression('/^'.CampaignRegistry::SLUG_PATTERN.'$/', $slug);
            $this->assertStringStartsWith(CampaignRegistry::SLUG_PREFIX, $slug);
        }
        $this->getJson(self::BASE.'/campaigns/about')->assertNotFound();
    }

    /** @effects admin_and_template_parity */
    public function test_admin_adapter_and_template_routes_match_the_registry(): void
    {
        $admin = file_get_contents(dirname(__DIR__, 3).'/resources/layouts/admin/admin_travel_lab_campaigns.json');
        preg_match_all("/p\\.slug === '([a-z0-9-]+)'/", $admin, $matches);
        $this->assertSame((new CampaignRegistry)->slugs(), array_values(array_unique($matches[1])));

        $routes = json_decode(file_get_contents(base_path('templates/_bundled/raonslab-travel_lab/routes.json')), true, flags: JSON_THROW_ON_ERROR);
        $paths = array_column($routes['routes'], 'layout', 'path');
        $this->assertSame('travel/campaign_detail', $paths['/travel/campaigns/:slug']);
        $this->assertSame('travel/campaign_detail', $paths['/page/:slug']);
        $this->assertSame('travel/campaigns', $paths['/travel/campaigns']);
    }
}
