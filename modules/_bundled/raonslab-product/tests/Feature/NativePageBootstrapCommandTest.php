<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once dirname(__DIR__, 3).'/sirsoft-page/tests/ModuleTestCase.php';

use App\Extension\HookManager;
use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Modules\Raonslab\Product\Providers\ProductServiceProvider;
use Modules\Raonslab\Product\Services\NativePageBootstrapper;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Models\PageVersion;
use Modules\Sirsoft\Page\Tests\ModuleTestCase as PageModuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class NativePageBootstrapCommandTest extends PageModuleTestCase
{
    /** @var array<int, string> */
    private array $payloadFiles = [];

    protected function setUp(): void
    {
        static::$migrated = false;
        parent::setUp();
        $this->app->register(ProductServiceProvider::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->payloadFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    #[Test]
    public function explicit_actor_is_used_without_login_logout_or_remember_token_mutation(): void
    {
        $otherAdmin = User::factory()->create(['is_super' => true, 'remember_token' => 'other-sentinel']);
        $actor = User::factory()->create(['is_super' => true, 'remember_token' => 'actor-sentinel']);
        $preexistingTargetCount = Page::whereIn('slug', NativePageBootstrapper::SLUGS)->count();
        $expectedCreated = count(NativePageBootstrapper::SLUGS) - $preexistingTargetCount;
        Event::fake([Authenticated::class, Login::class, Logout::class]);

        $exit = Artisan::call('raonslab-product:bootstrap-pages', [
            'payload' => $this->payloadFile(),
            '--actor' => $actor->email,
        ]);
        $this->assertSame(0, $exit, Artisan::output());

        $this->assertSame(
            $expectedCreated,
            Page::where('created_by', $actor->id)->count(),
            Artisan::output().' total='.Page::count().' unattributed='.Page::whereNull('created_by')->count(),
        );
        $this->assertSame(0, Page::where('created_by', $otherAdmin->id)->count());
        $this->assertSame($expectedCreated, PageVersion::where('created_by', $actor->id)->count());
        $this->assertSame(7, Page::whereIn('slug', NativePageBootstrapper::SLUGS)->count());
        $this->assertSame('actor-sentinel', $actor->fresh()->getRememberToken());
        $this->assertSame('other-sentinel', $otherAdmin->fresh()->getRememberToken());
        $this->assertNull(Auth::id());
        Event::assertNotDispatched(Authenticated::class);
        Event::assertNotDispatched(Login::class);
        Event::assertNotDispatched(Logout::class);
    }

    #[Test]
    public function partial_creation_failure_rolls_back_all_seven_pages_and_versions(): void
    {
        $actor = User::factory()->create(['is_super' => true, 'remember_token' => 'rollback-sentinel']);
        Page::whereIn('slug', NativePageBootstrapper::SLUGS)->get()->each->forceDelete();
        $pageCountBefore = Page::count();
        $versionCountBefore = PageVersion::count();
        $targetCountBefore = Page::whereIn('slug', NativePageBootstrapper::SLUGS)->count();
        $this->assertSame(0, $targetCountBefore);
        HookManager::addAction('sirsoft-page.page.before_create', function (array $data): void {
            if (($data['slug'] ?? null) === 'cases') {
                throw new RuntimeException('Synthetic partial failure.');
            }
        });

        $exit = Artisan::call('raonslab-product:bootstrap-pages', [
            'payload' => $this->payloadFile(),
            '--actor' => (string) $actor->id,
        ]);
        $this->assertSame(1, $exit, Artisan::output());

        $this->assertSame($targetCountBefore, Page::whereIn('slug', NativePageBootstrapper::SLUGS)->count());
        $this->assertSame($pageCountBefore, Page::count());
        $this->assertSame($versionCountBefore, PageVersion::count());
        $this->assertSame('rollback-sentinel', $actor->fresh()->getRememberToken());
        $this->assertNull(Auth::id());
    }

    #[Test]
    public function missing_or_invalid_actor_fails_before_page_writes(): void
    {
        $path = $this->payloadFile();
        $regularUser = User::factory()->create(['is_super' => false]);
        $blockedAdmin = User::factory()->create(['is_super' => true, 'blocked_at' => now()]);
        $pageCountBefore = Page::count();
        $versionCountBefore = PageVersion::count();
        $targetCountBefore = Page::whereIn('slug', NativePageBootstrapper::SLUGS)->count();

        $this->assertSame(1, Artisan::call('raonslab-product:bootstrap-pages', ['payload' => $path]));
        $this->assertSame(1, Artisan::call('raonslab-product:bootstrap-pages', [
            'payload' => $path,
            '--actor' => 'not-an-id-or-email',
        ]));
        $this->assertSame(1, Artisan::call('raonslab-product:bootstrap-pages', [
            'payload' => $path,
            '--actor' => (string) $regularUser->id,
        ]));
        $this->assertSame(1, Artisan::call('raonslab-product:bootstrap-pages', [
            'payload' => $path,
            '--actor' => $blockedAdmin->email,
        ]));

        $this->assertSame($targetCountBefore, Page::whereIn('slug', NativePageBootstrapper::SLUGS)->count());
        $this->assertSame($pageCountBefore, Page::count());
        $this->assertSame($versionCountBefore, PageVersion::count());
    }

    private function payloadFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'raon-native-page-test-');
        if ($file === false) {
            throw new RuntimeException('Unable to create a temporary payload file.');
        }
        $this->payloadFiles[] = $file;

        $payload = [];
        foreach (NativePageBootstrapper::SLUGS as $slug) {
            $payload[$slug] = [
                'title' => ['ko' => "{$slug} 제목", 'en' => "{$slug} title"],
                'content' => ['ko' => '<p>승인된 본문</p>', 'en' => '<p>Approved content</p>'],
                'content_mode' => 'html',
                'published' => true,
                'seo_meta' => ['title' => "{$slug} SEO", 'description' => 'Approved description'],
            ];
        }
        file_put_contents($file, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $file;
    }
}
