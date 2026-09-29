<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Modules\Raonslab\Product\Listeners\ApplyHomeSeoMeta;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductLayerContractTest extends ModuleTestCase
{
    #[Test]
    public function information_and_policy_documents_use_native_page_contract(): void
    {
        $moduleRoot = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents(
            $moduleRoot.'/module.json'
        ), true, flags: JSON_THROW_ON_ERROR);
        $extension = json_decode((string) file_get_contents(
            $moduleRoot.'/resources/extensions/native-page.json'
        ), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('>=1.1.2', $manifest['dependencies']['modules']['sirsoft-page']);
        $this->assertSame('page/show', $extension['target_layout']);
        $this->assertSame(
            [
                ['page_content_card', 'inject_props'],
                ['page_content_card', 'prepend_child'],
                ['page_html_content', 'prepend'],
                ['page_content_card', 'append_child'],
            ],
            array_map(
                fn (array $injection): array => [$injection['target_id'], $injection['position']],
                $extension['injections'],
            ),
        );

        // 분류 단일 출처의 11개 slug 가 모든 조건식에 같은 순서로 들어간다.
        $taxonomy = json_decode((string) file_get_contents(
            $moduleRoot.'/resources/taxonomy/info-policy.json'
        ), true, flags: JSON_THROW_ON_ERROR);
        $slugs = collect($taxonomy['groups'])->flatMap(fn (array $group) => array_column($group['items'], 'slug'))->all();
        $this->assertSame(
            ['about', 'service', 'cases', 'technology', 'faq', 'contact', 'privacy', 'terms', 'ai-workspace-policy', 'open-source', 'refund'],
            $slugs,
        );
        $condition = "['".implode("','", $slugs)."'].includes(page?.data?.slug)";
        $this->assertStringContainsString($condition, $extension['injections'][0]['props']['className']);
        foreach ([1, 2, 3] as $index) {
            $this->assertSame('{{'.$condition.'}}', $extension['injections'][$index]['components'][0]['if']);
        }
        $this->assertStringContainsString(
            'rh-native-page-card',
            $extension['injections'][0]['props']['className'],
        );
        $this->assertFalse(collect($extension['injections'])->contains(
            fn (array $injection): bool => $injection['target_id'] === 'page_html_content'
                && $injection['position'] === 'inject_props',
        ));
        $this->assertFileDoesNotExist(
            $moduleRoot.'/resources/routes/user.json'
        );

        foreach ([
            'rh_info_services.json',
            'rh_info_cases.json',
            'rh_info_principles.json',
            'rh_policy_privacy.json',
            'rh_policy_community.json',
            'rh_policy_ai_workspace.json',
            'rh_policy_open_source.json',
        ] as $layout) {
            $this->assertFileDoesNotExist(
                $moduleRoot.'/resources/layouts/user/'.$layout
            );
        }
    }

    #[Test]
    public function legacy_information_and_policy_urls_redirect_permanently_to_native_pages(): void
    {
        config(['app.locale' => 'ko', 'app.supported_locales' => ['ko', 'en']]);

        $routes = [
            '/info/services' => '/page/service',
            '/info/cases' => '/page/cases',
            '/info/principles' => '/page/technology',
            '/policy/privacy' => '/page/privacy',
            '/policy/community' => '/page/terms',
            '/policy/ai-workspace' => '/page/ai-workspace-policy',
            '/policy/open-source' => '/page/open-source',
        ];

        foreach ($routes as $legacy => $canonical) {
            $this->get($legacy.'?source=legacy')
                ->assertStatus(301)
                ->assertRedirect($canonical.'?source=legacy');

            $this->get('/en'.$legacy.'?source=legacy&locale=ko')
                ->assertStatus(301)
                ->assertRedirect($canonical.'?source=legacy&locale=en');

            $this->get('/ko'.$legacy.'?source=legacy&locale=en')
                ->assertStatus(301)
                ->assertRedirect($canonical.'?source=legacy');
        }

        $response = $this->get('/fr/info/services');
        $this->assertNotSame(301, $response->getStatusCode());
        $this->assertNotSame(301, $this->get('/info/services/extra')->getStatusCode());
    }

    #[Test]
    public function home_extension_replaces_only_the_home_content_host(): void
    {
        $path = base_path('modules/_bundled/raonslab-product/resources/extensions/home-product.json');
        $extension = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('home', $extension['target_layout']);
        $this->assertSame('main_content', $extension['injections'][0]['target_id']);
        $this->assertSame('replace', $extension['injections'][0]['position']);
    }

    #[Test]
    public function product_copy_does_not_expose_development_labels(): void
    {
        $paths = [
            base_path('modules/_bundled/raonslab-product/resources/lang/ko.json'),
            base_path('modules/_bundled/raonslab-product/resources/lang/en.json'),
        ];

        foreach ($paths as $path) {
            $copy = strtoupper((string) file_get_contents($path));
            $this->assertDoesNotMatchRegularExpression('/\b(DEMO|MOCK|SANDBOX|TEST)\b/', $copy);
        }
    }

    #[Test]
    public function home_seo_meta_uses_the_module_owned_localized_config(): void
    {
        $listener = new ApplyHomeSeoMeta;
        $meta = $listener->applyHomeMeta(
            ['title' => '', 'description' => '', 'og' => [], 'twitter' => []],
            ['layoutName' => 'home', 'locale' => 'ko']
        );

        $this->assertStringStartsWith('RAON Agent Factory', $meta['title']);
        $this->assertStringContainsString('업무 분석', $meta['description']);
        $this->assertSame($meta['title'], $meta['og']['title']);
        $this->assertSame($meta['description'], $meta['twitter']['description']);

        $unrelated = $listener->applyHomeMeta(
            ['title' => '게시판'],
            ['layoutName' => 'board_list', 'locale' => 'ko']
        );
        $this->assertSame(['title' => '게시판'], $unrelated);
    }

    #[Test]
    public function home_seo_context_is_marked_static_closed_only_for_the_home_layout(): void
    {
        $listener = new ApplyHomeSeoMeta;
        $this->assertSame(
            ['method' => 'markHomeStaticContext', 'priority' => 10, 'type' => 'filter'],
            ApplyHomeSeoMeta::getSubscribedHooks()['core.seo.filter_context'],
        );
        $this->assertSame('applyHomeMeta', ApplyHomeSeoMeta::getSubscribedHooks()['core.seo.filter_meta']['method']);

        $home = $listener->markHomeStaticContext(
            ['_local' => ['kept' => 1], '_global' => ['x' => 'y']],
            ['layoutName' => 'home', 'locale' => 'ko'],
        );
        $this->assertTrue($home['_local']['raonStaticClosedFallback']);
        $this->assertSame(1, $home['_local']['kept']);
        $this->assertSame(['x' => 'y'], $home['_global']);
        $this->assertTrue($listener->markHomeStaticContext([], ['layoutName' => 'home'])['_local']['raonStaticClosedFallback']);

        // 홈이 아닌 컨텍스트는 그대로(직렬화 결과까지 동일) 돌려준다
        foreach (['page/show', 'board_list', 'home_extra', null] as $layoutName) {
            $context = ['_local' => ['a' => [1, 2]], 'route' => ['slug' => 'cases'], 'query' => []];
            $result = $listener->markHomeStaticContext($context, ['layoutName' => $layoutName]);
            $this->assertSame(serialize($context), serialize($result));
        }
    }
}
