<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once dirname(__DIR__, 3).'/sirsoft-page/tests/ModuleTestCase.php';

use App\Extension\HookManager;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Modules\Raonslab\Product\Console\Commands\RemediateInfoPagesCommand;
use Modules\Raonslab\Product\Providers\ProductServiceProvider;
use Modules\Raonslab\Product\Services\InfoPageRemediator;
use Modules\Raonslab\Product\Services\NativePageContentPack;
use Modules\Sirsoft\Page\Database\Seeders\PageSeeder;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Models\PageVersion;
use Modules\Sirsoft\Page\Tests\ModuleTestCase as PageModuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * 운영자 전용 about/faq/contact/refund 교체 명령의 사전 조건·전부 아니면 전무·멱등성을 고정한다.
 * 픽스처는 sirsoft-page 의 실제 PageSeeder 원문이고, 교체 본문은 합성 문구다(운영 본문을 저장소에 두지 않는다).
 */
class InfoPageRemediationCommandTest extends PageModuleTestCase
{
    private const SLUGS = ['about', 'faq', 'contact', 'refund'];

    /** @var array<int, string> */
    private array $payloadFiles = [];

    private User $actor;

    protected function setUp(): void
    {
        static::$migrated = false;
        parent::setUp();
        $this->app->register(ProductServiceProvider::class);
        // 활성 설치본이 없는 base path(요청 worktree 등)에서는 Artisan 이 provider 등록보다 먼저 만들어져
        // provider 의 commands() 가 반영되지 않는다. 설치 상태와 무관하게 명령을 명시 등록한다.
        $this->app->make(ConsoleKernel::class)->registerCommand($this->app->make(RemediateInfoPagesCommand::class));

        Page::whereIn('slug', self::SLUGS)->get()->each->forceDelete();
        $this->runPageSeeder();
        $this->assertSame(4, Page::whereIn('slug', self::SLUGS)->count(), 'PageSeeder fixture');
        $this->actor = User::factory()->create(['is_super' => true, 'remember_token' => 'actor-sentinel']);
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
    public function untouched_seeder_samples_match_the_audited_source_fingerprints(): void
    {
        foreach (self::SLUGS as $slug) {
            $page = Page::where('slug', $slug)->firstOrFail();
            $this->assertSame(1, (int) $page->current_version);
            $this->assertSame(
                InfoPageRemediator::SOURCE_FINGERPRINTS[$slug],
                InfoPageRemediator::fingerprint($slug, $page->only(['title', 'content', 'content_mode', 'seo_meta'])),
                $slug,
            );
        }
    }

    #[Test]
    public function dry_run_classifies_all_four_and_writes_nothing(): void
    {
        $before = $this->state();

        $exit = $this->remediate([
            'pack' => $this->payloadFile(),
            '--actor' => (string) $this->actor->id,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();
        $this->assertSame(0, $exit, $output);
        foreach (self::SLUGS as $slug) {
            $this->assertStringContainsString("{$slug}: would_update", $output);
        }
        $this->assertMatchesRegularExpression('/pack_sha256: [0-9a-f]{64} \(not checked\)/', $output);
        $this->assertStringContainsString('schema: '.NativePageContentPack::SCHEMA, $output);
        $this->assertStringContainsString('pack_id: synthetic-pack', $output);
        $this->assertStringContainsString('base_commit: '.InfoPageRemediator::AUDITED_BASE_COMMIT, $output);
        $this->assertSame($before, $this->state());
    }

    #[Test]
    public function apply_updates_through_page_service_with_actor_attribution_then_is_idempotent(): void
    {
        Event::fake([Login::class]);
        $otherAdmin = User::factory()->create(['is_super' => true]);
        $publishedAt = Page::whereIn('slug', self::SLUGS)->pluck('published_at', 'slug')->map(fn ($value) => (string) $value)->all();
        $versionRowsBefore = PageVersion::count();
        $payload = $this->payloadFile();

        $exit = $this->remediate([
            'pack' => $payload,
            '--actor' => $this->actor->email,
        ]);
        $this->assertSame(0, $exit, Artisan::output());

        foreach (self::SLUGS as $slug) {
            $page = Page::where('slug', $slug)->firstOrFail();
            $version = PageVersion::where('page_id', $page->id)->where('version', 2)->firstOrFail();
            $this->assertSame(2, (int) $page->current_version, $slug);
            $this->assertTrue((bool) $page->published, $slug);
            $this->assertSame($publishedAt[$slug], (string) $page->published_at, $slug);
            $this->assertSame($this->actor->id, (int) $page->updated_by, $slug);
            $this->assertSame($this->actor->id, (int) $version->created_by, $slug);
            $this->assertSame($this->approved()['pages'][$slug]['title'], $page->title, $slug);
            $this->assertSame($this->approved()['pages'][$slug]['seo_meta'], $page->seo_meta, $slug);
            $this->assertSame($page->content, $version->content, $slug);
        }
        $this->assertSame($versionRowsBefore + 4, PageVersion::count());
        $this->assertSame(0, Page::where('updated_by', $otherAdmin->id)->count());
        $this->assertNull(Auth::id());
        $this->assertSame('actor-sentinel', $this->actor->fresh()->getRememberToken());
        Event::assertNotDispatched(Login::class);

        $after = $this->state();
        $exit = $this->remediate([
            'pack' => $payload,
            '--actor' => (string) $this->actor->id,
        ]);
        $output = Artisan::output();
        $this->assertSame(0, $exit, $output);
        foreach (self::SLUGS as $slug) {
            $this->assertStringContainsString("{$slug}: already_applied", $output);
        }
        $this->assertSame($after, $this->state());
    }

    #[Test]
    public function one_edited_page_aborts_all_four_without_writes(): void
    {
        $faq = Page::where('slug', 'faq')->firstOrFail();
        // 관리자가 이미 손댄 상태를 흉내 낸다(픽스처 준비용 직접 변경).
        $faq->forceFill(['title' => ['ko' => '관리자 수정', 'en' => 'Edited by admin']])->saveQuietly();
        $before = $this->state();

        $exit = $this->remediate([
            'pack' => $this->payloadFile(),
            '--actor' => (string) $this->actor->id,
        ]);

        $output = Artisan::output();
        $this->assertSame(1, $exit, $output);
        $this->assertStringContainsString('faq: conflict (unexpected_source_fingerprint)', $output);
        $this->assertStringContainsString('Aborted without writing any Page.', $output);
        $this->assertSame($before, $this->state());
    }

    #[Test]
    public function unexpected_version_or_unpublished_page_aborts_without_writes(): void
    {
        Page::where('slug', 'contact')->firstOrFail()->forceFill(['current_version' => 3])->saveQuietly();
        Page::where('slug', 'refund')->firstOrFail()->forceFill(['published' => false])->saveQuietly();
        $before = $this->state();

        $exit = $this->remediate([
            'pack' => $this->payloadFile(),
            '--actor' => (string) $this->actor->id,
        ]);

        $output = Artisan::output();
        $this->assertSame(1, $exit, $output);
        $this->assertStringContainsString('contact: conflict (unexpected_version:3)', $output);
        $this->assertStringContainsString('refund: conflict (not_published)', $output);
        $this->assertSame($before, $this->state());
    }

    #[Test]
    public function failure_during_writes_rolls_back_every_page_and_version(): void
    {
        HookManager::addAction('sirsoft-page.page.before_update', function (Page $page): void {
            if ($page->slug === 'contact') {
                throw new RuntimeException('Synthetic mid-write failure.');
            }
        });
        $before = $this->state();

        $exit = $this->remediate([
            'pack' => $this->payloadFile(),
            '--actor' => (string) $this->actor->id,
        ]);

        $this->assertSame(1, $exit, Artisan::output());
        $this->assertSame($before, $this->state());
        $this->assertNull(Auth::id());
    }

    #[Test]
    public function invalid_payloads_and_actors_fail_before_any_write(): void
    {
        $before = $this->state();
        $regularUser = User::factory()->create(['is_super' => false]);
        $blockedAdmin = User::factory()->create(['is_super' => true, 'blocked_at' => now()]);
        $valid = $this->approved();

        $pages = $valid['pages'];
        $pack = fn (array $override): array => array_replace_recursive($valid, $override);

        $invalid = [
            'bare page map without envelope' => $pages,
            'unknown schema' => $pack(['schema' => 'raonslab-product.native-page-content-pack.v0']),
            'missing pack_id' => array_diff_key($valid, ['pack_id' => true]),
            'short base_commit' => $pack(['base_commit' => 'abc123']),
            'other baseline commit' => $pack(['base_commit' => str_repeat('b', 40)]),
            'uppercase base_commit' => $pack(['base_commit' => strtoupper(InfoPageRemediator::AUDITED_BASE_COMMIT)]),
            'empty pack_id' => $pack(['pack_id' => '   ']),
            'pages as list' => ['pages' => array_values($pages)] + $valid,
            'extra envelope key' => $valid + ['notes' => 'x'],
            'missing slug' => ['pages' => array_diff_key($pages, ['refund' => true])] + $valid,
            'extra slug' => ['pages' => $pages + ['terms' => $pages['about']]] + $valid,
            'unpublish request' => $pack(['pages' => ['faq' => ['published' => false]]]),
            'placeholder text' => $pack(['pages' => ['contact' => ['content' => ['ko' => '<p>[연락처 정보를 입력하세요.]</p>']]]]),
            'development label' => $pack(['pages' => ['about' => ['title' => ['en' => 'About TEST']]]]),
            'text mode' => $pack(['pages' => ['refund' => ['content_mode' => 'text']]]),
            'missing seo title' => $pack(['pages' => ['faq' => ['seo_meta' => ['title' => '']]]]),
            'extra locale' => $pack(['pages' => ['about' => ['title' => ['ja' => '概要']]]]),
            'unknown page field' => $pack(['pages' => ['about' => ['slug' => 'about-us']]]),
        ];

        foreach ($invalid as $case => $payload) {
            $exit = $this->remediate([
                'pack' => $this->payloadFile($payload),
                '--actor' => (string) $this->actor->id,
            ]);
            $this->assertSame(1, $exit, $case.': '.Artisan::output());
        }

        $path = $this->payloadFile();
        foreach (['', 'not-an-id', (string) $regularUser->id, $blockedAdmin->email] as $actor) {
            $this->assertSame(1, $this->remediate(['pack' => $path, '--actor' => $actor]), $actor);
        }
        $this->assertSame(1, $this->remediate([
            'pack' => 'relative/payload.json',
            '--actor' => (string) $this->actor->id,
        ]));

        $this->assertSame($before, $this->state());
    }

    #[Test]
    public function module_lifecycle_never_invokes_the_remediation(): void
    {
        $moduleRoot = dirname(__DIR__, 2);
        $lifecycleSources = array_merge(
            [$moduleRoot.'/module.php'],
            glob($moduleRoot.'/upgrades/*.php') ?: [],
            glob($moduleRoot.'/database/migrations/*.php') ?: [],
            glob($moduleRoot.'/src/Providers/*.php') ?: [],
        );

        foreach ($lifecycleSources as $file) {
            $source = (string) file_get_contents($file);
            $this->assertStringNotContainsString('InfoPageRemediator', $source, $file);
            $this->assertStringNotContainsString("'raonslab-product:remediate-info-pages'", $source, $file);
        }
    }

    /** sirsoft-page 공식 PageSeeder 로 v1 샘플을 만든다(이미 있는 slug 는 시더가 건너뛴다). */
    private function runPageSeeder(): void
    {
        $command = new Command;
        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

        $seeder = $this->app->make(PageSeeder::class);
        $seeder->setContainer($this->app)->setCommand($command);
        $seeder->run();
    }

    #[Test]
    public function apply_requires_the_approved_whole_file_sha256(): void
    {
        $before = $this->state();
        $file = $this->payloadFile();

        $this->assertSame(1, Artisan::call('raonslab-product:remediate-info-pages', [
            'pack' => $file,
            '--actor' => (string) $this->actor->id,
        ]));
        $this->assertStringContainsString('Applying requires --sha256', Artisan::output());

        $this->assertSame(1, $this->remediate(['pack' => $file, '--sha256' => str_repeat('0', 64)]));
        $this->assertStringContainsString('does not match the approved value', Artisan::output());

        $this->assertSame(1, $this->remediate(['pack' => $file, '--sha256' => 'not-a-sha']));
        $this->assertSame($before, $this->state());

        $this->assertSame(0, $this->remediate(['pack' => $file, '--sha256' => strtoupper(hash_file('sha256', $file))]));
        $this->assertMatchesRegularExpression('/pack_sha256: [0-9a-f]{64} \(matches approved\)/', Artisan::output());
    }

    #[Test]
    public function pack_envelope_is_validated_before_any_page_is_read(): void
    {
        $pack = NativePageContentPack::fromFile($this->payloadFile(), InfoPageRemediator::AUDITED_BASE_COMMIT);

        $this->assertSame('synthetic-pack', $pack->packId);
        $this->assertSame(InfoPageRemediator::AUDITED_BASE_COMMIT, $pack->baseCommit);
        $this->assertSame(self::SLUGS, array_keys($pack->pages));
        $this->assertArrayNotHasKey('schema', $pack->pages);

        $this->expectException(\InvalidArgumentException::class);
        NativePageContentPack::fromFile($this->payloadFile(), str_repeat('c', 40));
    }

    /** 승인 SHA-256 을 기본으로 붙여 명령을 실행한다(dry-run 이거나 명시한 값이 있으면 그대로). */
    private function remediate(array $arguments): int
    {
        $arguments += ['--actor' => (string) $this->actor->id];
        if (empty($arguments['--dry-run']) && ! array_key_exists('--sha256', $arguments) && is_file((string) ($arguments['pack'] ?? ''))) {
            $arguments['--sha256'] = hash_file('sha256', $arguments['pack']);
        }

        return Artisan::call('raonslab-product:remediate-info-pages', $arguments);
    }

    /** @return array<string, array<string, mixed>> */
    private function state(): array
    {
        return Page::whereIn('slug', self::SLUGS)->orderBy('slug')->get()
            ->mapWithKeys(fn (Page $page) => [$page->slug => [
                'version' => (int) $page->current_version,
                'published' => (bool) $page->published,
                'updated_by' => $page->updated_by,
                'fingerprint' => InfoPageRemediator::fingerprint($page->slug, $page->only(['title', 'content', 'content_mode', 'seo_meta'])),
                'version_rows' => PageVersion::where('page_id', $page->id)->count(),
            ]])->all();
    }

    /**
     * 콘텐츠 lane 의 content pack v1 과 같은 envelope 에 합성 본문을 담는다.
     *
     * @return array<string, mixed>
     */
    private function approved(): array
    {
        $pages = [];
        foreach (self::SLUGS as $slug) {
            $pages[$slug] = [
                'title' => ['ko' => "{$slug} 승인 제목", 'en' => "{$slug} approved title"],
                'content' => ['ko' => "<p>{$slug} 승인 본문</p>", 'en' => "<p>{$slug} approved body</p>"],
                'content_mode' => 'html',
                'published' => true,
                'seo_meta' => ['title' => "{$slug} SEO", 'description' => "{$slug} approved description", 'keywords' => "{$slug}, synthetic"],
            ];
        }

        return [
            'base_commit' => InfoPageRemediator::AUDITED_BASE_COMMIT,
            'pack_id' => 'synthetic-pack',
            'pages' => $pages,
            'schema' => NativePageContentPack::SCHEMA,
        ];
    }

    private function payloadFile(?array $payload = null): string
    {
        $file = tempnam(sys_get_temp_dir(), 'raon-info-page-test-');
        if ($file === false) {
            throw new RuntimeException('Unable to create a temporary payload file.');
        }
        $this->payloadFiles[] = $file;
        file_put_contents($file, json_encode($payload ?? $this->approved(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $file;
    }
}
