<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Modules\Raonslab\Product\Listeners\ApplyHomeSeoMeta;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductLayerContractTest extends ModuleTestCase
{
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
