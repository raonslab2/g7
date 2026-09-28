<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once dirname(__DIR__, 3).'/sirsoft-board/tests/ModuleTestCase.php';

use App\Extension\HookManager;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Raonslab\Product\Console\Commands\SeedQaContentCommand;
use Modules\Raonslab\Product\Listeners\SuppressQaContentNotifications;
use Modules\Raonslab\Product\Module;
use Modules\Raonslab\Product\Providers\ProductServiceProvider;
use Modules\Sirsoft\Board\Models\Attachment;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Comment;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Tests\ModuleTestCase as BoardModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class QaContentCommandTest extends BoardModuleTestCase
{
    private Board $board;

    protected function runModuleMigrationIfNeeded(): void
    {
        if (Schema::hasTable('boards')
            && Schema::hasTable('board_posts')
            && Schema::hasColumn('board_posts', 'content_thumbnail_url')) {
            static::$migrated = true;

            return;
        }

        parent::runModuleMigrationIfNeeded();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ProductServiceProvider::class);
        $this->app->make(Kernel::class)->registerCommand($this->app->make(SeedQaContentCommand::class));
        $this->board = Board::updateOrCreate(['slug' => 'questions'], [
            'name' => ['ko' => '질문과 답변', 'en' => 'Q&A'],
            'is_active' => true,
            'type' => 'basic',
            'categories' => [],
            'use_reply' => true,
            'max_reply_depth' => 5,
            'order_by' => 'created_at',
            'order_direction' => 'DESC',
            'per_page' => 20,
            'per_page_mobile' => 15,
        ]);
        Post::withTrashed()->where('board_id', $this->board->id)->forceDelete();

        $author = User::query()->where('name', '공식 운영자')->where('is_super', true)->first();
        if (! $author) {
            $author = $this->createAdminUser();
            $author->update([
                'name' => '공식 운영자',
                'is_super' => true,
                'status' => 'active',
            ]);
        }

        $permission = Permission::firstOrCreate(
            ['identifier' => 'sirsoft-board.questions.posts.read'],
            ['name' => ['ko' => '질문과 답변 읽기', 'en' => 'Read Q&A'], 'type' => 'user'],
        );
        Role::where('identifier', 'guest')->firstOrFail()->permissions()->syncWithoutDetaching([$permission->id]);
    }

    #[Test]
    public function fixture_has_eight_safe_source_based_scenarios(): void
    {
        $fixture = json_decode((string) file_get_contents(
            base_path('modules/_bundled/raonslab-product/database/content/agent-factory-qa.v1.json')
        ), true, flags: JSON_THROW_ON_ERROR);

        $this->assertCount(8, $fixture['scenarios']);
        $this->assertSame('synthetic_operational_guidance', $fixture['provenance']['kind']);
        $this->assertSame('questions', $fixture['board']['slug']);

        foreach ($fixture['scenarios'] as $scenario) {
            $visible = $scenario['question']['title'].$scenario['question']['content']
                .$scenario['answer']['title'].$scenario['answer']['content'];
            $this->assertStringStartsWith('[자주 묻는 질문] ', $scenario['question']['title']);
            $this->assertStringEndsWith(
                '<p>현재 접수 가능 여부는 홈의 구축 상담 영역에서 확인해 주세요.</p>',
                $scenario['answer']['content']
            );
            $this->assertDoesNotMatchRegularExpression('/\b(?:demo|mock|sandbox|test)\b/i', $visible);
            $this->assertDoesNotMatchRegularExpression('/SLA|24\s*시간\s*지원|고객사|납품|매출|절감률/u', $visible);
            $this->assertNotEmpty($scenario['basis']);
        }

        $byKey = collect($fixture['scenarios'])->keyBy('key');
        $this->assertStringContainsString('프로그램 소스', $byKey['data-and-permissions']['question']['title']);
        $this->assertStringContainsString('내부 설정값·원본 기록', $byKey['data-and-permissions']['answer']['content']);
        foreach (['통과', '실패', '미검증', '환경 제약'] as $term) {
            $this->assertStringContainsString($term, $byKey['verification-status']['answer']['content']);
        }
        $q8Visible = $byKey['opensource-upstream']['question']['title']
            .$byKey['opensource-upstream']['answer']['title']
            .$byKey['opensource-upstream']['answer']['content'];
        $this->assertDoesNotMatchRegularExpression('/upstream|stable|lifecycle|hook|훅|수명주기\s*명령/i', $q8Visible);
    }

    #[Test]
    public function dry_run_first_apply_and_second_apply_are_idempotent(): void
    {
        $this->artisan('raonslab-product:qa-content', ['--dry-run' => true])
            ->expectsOutputToContain('create=16')
            ->assertSuccessful();
        $this->assertSame(0, Post::where('board_id', $this->board->id)->count());

        $this->artisan('raonslab-product:qa-content')
            ->expectsOutputToContain('created=16')
            ->expectsOutputToContain('duplicates=0')
            ->assertSuccessful();

        $this->assertSame(16, Post::where('board_id', $this->board->id)->count());
        $this->assertSame(8, Post::where('board_id', $this->board->id)->whereNull('parent_id')->count());
        $this->assertSame(8, Post::where('board_id', $this->board->id)->whereNotNull('parent_id')->count());

        $this->artisan('raonslab-product:qa-content')
            ->expectsOutputToContain('created=0')
            ->expectsOutputToContain('duplicates=0')
            ->assertSuccessful();
        $this->assertSame(16, Post::where('board_id', $this->board->id)->count());
    }

    #[Test]
    public function public_list_detail_search_and_mobile_page_contract_expose_answers(): void
    {
        $this->artisan('raonslab-product:qa-content')->assertSuccessful();

        $question = Post::where('board_id', $this->board->id)
            ->where('title', '[자주 묻는 질문] 업무 한 개를 먼저 실증하면 어디까지 확인하나요?')
            ->firstOrFail();
        $answer = Post::where('board_id', $this->board->id)
            ->where('parent_id', $question->id)
            ->firstOrFail();
        $upstreamQuestion = Post::where('board_id', $this->board->id)
            ->where('title', '[자주 묻는 질문] 오픈소스 기반 확장과 원본 프로젝트 업데이트는 어떻게 함께 유지하나요?')
            ->firstOrFail();

        $this->getJson('/api/modules/sirsoft-board/boards/questions/posts')
            ->assertOk()
            ->assertJsonPath('data.board.slug', 'questions')
            ->assertJsonPath('data.data.0.title', $question->title)
            ->assertJsonFragment(['title' => $question->title])
            ->assertJsonFragment(['title' => $answer->title]);

        $this->getJson("/api/modules/sirsoft-board/boards/questions/posts/{$question->id}")
            ->assertOk()
            ->assertJsonPath('data.title', $question->title)
            ->assertJsonPath('data.category', null)
            ->assertJsonPath('data.status', 'published')
            ->assertJsonCount(1, 'data.replies')
            ->assertJsonPath('data.replies.0.title', $answer->title);

        // DatabaseTransactions의 미커밋 행은 MySQL FULLTEXT 인덱스에 보이지 않는다.
        // 공개 검색의 공식 LIKE fallback으로 동일 request/response 노출 계약을 검증한다.
        config(['scout.driver' => 'null']);
        $this->getJson('/api/search?q=원본%20프로젝트&type=posts&board_slug=questions')
            ->assertOk()
            ->assertJsonFragment(['title' => $upstreamQuestion->title]);

        $mobile = $this->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36'
        )->getJson('/api/modules/sirsoft-board/boards/questions/posts');
        $mobile->assertOk()->assertJsonPath('data.pagination.per_page', 15);
    }

    #[Test]
    public function rollback_only_removes_provenance_rows_and_reapply_restores_them(): void
    {
        $unrelated = Post::create([
            'board_id' => $this->board->id,
            'title' => '기존 일반 게시글',
            'content' => '기존 일반 게시글 내용입니다.',
            'ip_address' => '127.0.0.1',
        ]);
        $this->artisan('raonslab-product:qa-content')->assertSuccessful();

        $this->artisan('raonslab-product:qa-content', ['--rollback' => true, '--dry-run' => true])
            ->expectsOutputToContain('rows=16')
            ->assertSuccessful();
        $this->assertSame(17, Post::where('board_id', $this->board->id)->count());

        $this->artisan('raonslab-product:qa-content', ['--rollback' => true])
            ->expectsOutputToContain('rows=16')
            ->assertSuccessful();
        $this->assertSame(1, Post::where('board_id', $this->board->id)->count());
        $this->assertTrue($unrelated->fresh()->exists);

        $this->artisan('raonslab-product:qa-content')->assertSuccessful();
        $this->assertSame(17, Post::where('board_id', $this->board->id)->count());
        $this->assertSame(16, Post::where('board_id', $this->board->id)
            ->where('action_logs', 'like', '%raonslab.agent_factory.qa.wave1_4%')->count());
    }

    /** @return array<string, array{string}> */
    public static function externalInteractionProvider(): array
    {
        return [
            'reply' => ['reply'],
            'comment' => ['comment'],
            'attachment' => ['attachment'],
        ];
    }

    #[Test]
    #[DataProvider('externalInteractionProvider')]
    public function rollback_aborts_without_mutation_when_external_interaction_exists(string $interaction): void
    {
        $this->artisan('raonslab-product:qa-content')->assertSuccessful();
        $root = Post::where('board_id', $this->board->id)->whereNull('parent_id')->firstOrFail();

        match ($interaction) {
            'reply' => Post::create([
                'board_id' => $this->board->id,
                'title' => '별도 작성 답글',
                'content' => '별도 작성자가 남긴 답글입니다.',
                'parent_id' => $root->id,
                'depth' => 1,
                'ip_address' => '127.0.0.1',
            ]),
            'comment' => Comment::create([
                'board_id' => $this->board->id,
                'post_id' => $root->id,
                'content' => '별도 작성자가 남긴 댓글입니다.',
                'ip_address' => '127.0.0.1',
            ]),
            'attachment' => Attachment::create([
                'board_id' => $this->board->id,
                'post_id' => $root->id,
                'original_filename' => 'reference.txt',
                'stored_filename' => 'reference.txt',
                'disk' => 'local',
                'path' => 'qa/reference.txt',
                'mime_type' => 'text/plain',
                'size' => 9,
                'collection' => 'default',
            ]),
        };

        $before = $this->postState();
        $this->artisan('raonslab-product:qa-content', ['--rollback' => true])
            ->expectsOutputToContain('Rollback blocked')
            ->assertFailed();

        $this->assertSame($before, $this->postState());
        $this->assertSame(16, Post::where('board_id', $this->board->id)
            ->where('action_logs', 'like', '%raonslab.agent_factory.qa.wave1_4%')->count());
    }

    /** @return array<string, array{string}> */
    public static function topologyTamperProvider(): array
    {
        return [
            'question depth' => ['question'],
            'answer parent and depth' => ['answer'],
        ];
    }

    #[Test]
    #[DataProvider('topologyTamperProvider')]
    public function apply_fails_closed_instead_of_repairing_tampered_topology(string $role): void
    {
        $this->artisan('raonslab-product:qa-content')->assertSuccessful();
        $post = $role === 'question'
            ? Post::where('board_id', $this->board->id)->whereNull('parent_id')->firstOrFail()
            : Post::where('board_id', $this->board->id)->whereNotNull('parent_id')->firstOrFail();

        $post->update($role === 'question'
            ? ['depth' => 1]
            : ['parent_id' => null, 'depth' => 0]);
        $before = $this->postState();

        $this->artisan('raonslab-product:qa-content')
            ->expectsOutputToContain('Topology mismatch')
            ->assertFailed();

        $this->assertSame($before, $this->postState());
    }

    #[Test]
    public function concurrent_provenance_lock_fails_without_creating_rows(): void
    {
        $key = SeedQaContentCommand::lockName(SuppressQaContentNotifications::PROVENANCE_KEY);
        Cache::lock($key, 300)->forceRelease();
        $holder = Cache::lock($key, 300);
        $this->assertTrue($holder->get());

        try {
            $this->artisan('raonslab-product:qa-content')
                ->expectsOutputToContain('already running')
                ->assertFailed();
            $this->assertSame(0, Post::where('board_id', $this->board->id)->count());
        } finally {
            $holder->release();
        }
    }

    #[Test]
    public function delete_and_restore_notification_extraction_is_suppressed_only_for_owned_provenance(): void
    {
        $this->assertContains(
            SuppressQaContentNotifications::class,
            (new Module)->getHookListeners()
        );

        $listener = new SuppressQaContentNotifications;
        $filter = fn (array $value, string $type, array $args): array => $listener->suppressOwnedContent($value, $type, $args);
        HookManager::addFilter('sirsoft-board.notification.extract_data', $filter, 90);

        $observed = [];
        $sent = 0;
        $enabled = true;
        HookManager::addAction('core.notification.after_send', function () use (&$sent, &$enabled): void {
            if ($enabled) {
                $sent++;
            }
        }, 99);
        $probe = function (Post $post, string $slug, array $options = []) use (&$observed, &$enabled): void {
            if (! $enabled) {
                return;
            }
            $observed[] = HookManager::applyFilters(
                'sirsoft-board.notification.extract_data',
                ['notifiable' => new \stdClass, 'notifiables' => null, 'data' => ['candidate' => true], 'context' => []],
                'post_action',
                [$post, $slug, $options]
            );
        };
        HookManager::addAction('sirsoft-board.post.after_delete', $probe, 99);
        HookManager::addAction('sirsoft-board.post.after_restore', $probe, 99);

        try {
            $this->artisan('raonslab-product:qa-content')->assertSuccessful();
            $this->artisan('raonslab-product:qa-content', ['--rollback' => true])->assertSuccessful();
            $this->artisan('raonslab-product:qa-content')->assertSuccessful();
        } finally {
            $enabled = false;
            HookManager::removeFilter('sirsoft-board.notification.extract_data', $filter);
        }

        $this->assertCount(32, $observed);
        $this->assertSame(0, $sent);
        foreach ($observed as $result) {
            $this->assertTrue($result['context']['skip'] ?? false);
            $this->assertNull($result['notifiable']);
            $this->assertSame([], $result['data']);
        }

        $unrelated = new Post(['action_logs' => []]);
        $candidate = ['notifiable' => new \stdClass, 'notifiables' => null, 'data' => ['candidate' => true], 'context' => []];
        $this->assertSame($candidate, $listener->suppressOwnedContent($candidate, 'post_action', [$unrelated, 'questions']));
    }

    /** @return array<int, array<string, mixed>> */
    private function postState(): array
    {
        return Post::withTrashed()
            ->where('board_id', $this->board->id)
            ->orderBy('id')
            ->get()
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'parent_id' => $post->parent_id,
                'depth' => $post->depth,
                'status' => $post->status->value,
                'deleted_at' => $post->deleted_at?->toISOString(),
                'updated_at' => $post->updated_at?->toISOString(),
            ])->all();
    }
}
