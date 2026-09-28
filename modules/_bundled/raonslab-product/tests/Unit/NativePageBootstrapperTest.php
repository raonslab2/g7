<?php

namespace Modules\Raonslab\Product\Tests\Unit;

use InvalidArgumentException;
use Mockery;
use Modules\Raonslab\Product\Services\NativePageBootstrapper;
use Modules\Sirsoft\Page\Services\PageService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NativePageBootstrapperTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_only_missing_slugs_and_never_updates_existing_pages(): void
    {
        $pageService = Mockery::mock(PageService::class);
        $payload = $this->payload();

        foreach (NativePageBootstrapper::SLUGS as $slug) {
            $pageService->shouldReceive('slugExists')->once()->with($slug)->andReturn($slug === 'privacy');
        }
        $pageService->shouldReceive('createPage')->times(6)->withArgs(
            fn (array $data): bool => $data['slug'] !== 'privacy' && $data['title']['ko'] === "{$data['slug']} ko"
        );
        $pageService->shouldNotReceive('updatePage');

        $results = (new NativePageBootstrapper($pageService))->bootstrap($payload);

        $this->assertSame('preserved_existing', $results['privacy']);
        $this->assertSame('created', $results['service']);
    }

    #[Test]
    public function dry_run_validates_but_does_not_write(): void
    {
        $pageService = Mockery::mock(PageService::class);
        $pageService->shouldReceive('slugExists')->times(7)->andReturn(false);
        $pageService->shouldNotReceive('createPage');

        $results = (new NativePageBootstrapper($pageService))->bootstrap($this->payload(), dryRun: true);

        $this->assertSame(array_fill_keys(NativePageBootstrapper::SLUGS, 'would_create'), $results);
    }

    #[Test]
    public function it_fails_before_writes_when_the_slug_set_is_not_exact(): void
    {
        $pageService = Mockery::mock(PageService::class);
        $pageService->shouldNotReceive('slugExists');
        $pageService->shouldNotReceive('createPage');
        $payload = $this->payload();
        unset($payload['terms']);

        $this->expectException(InvalidArgumentException::class);
        (new NativePageBootstrapper($pageService))->bootstrap($payload);
    }

    /** @return array<string, array<string, mixed>> */
    private function payload(): array
    {
        $payload = [];
        foreach (NativePageBootstrapper::SLUGS as $slug) {
            $payload[$slug] = [
                'title' => ['ko' => "{$slug} ko", 'en' => "{$slug} en"],
                'content' => ['ko' => '<p>ko</p>', 'en' => '<p>en</p>'],
                'content_mode' => 'html',
                'published' => true,
                'seo_meta' => ['title' => "{$slug} SEO", 'description' => 'description'],
            ];
        }

        return $payload;
    }
}
