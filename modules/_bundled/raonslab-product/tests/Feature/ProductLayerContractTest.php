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
            ['page_content_card', 'page_content_card', 'page_html_content', 'page_html_content'],
            array_column($extension['injections'], 'target_id')
        );
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

            $this->get('/en'.$legacy)
                ->assertStatus(301)
                ->assertRedirect('/en'.$canonical);
        }
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
}
