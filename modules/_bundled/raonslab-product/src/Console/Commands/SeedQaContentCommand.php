<?php

namespace Modules\Raonslab\Product\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Sirsoft\Board\Enums\PostStatus;
use Modules\Sirsoft\Board\Enums\TriggerType;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Services\PostService;
use RuntimeException;

class SeedQaContentCommand extends Command
{
    protected $signature = 'raonslab-product:qa-content
        {--board=questions : Existing Q&A board slug}
        {--author= : Existing active super administrator UUID}
        {--dry-run : Validate and report without writing}
        {--rollback : Soft-delete only content carrying this fixture provenance}
        {--force : Allow writes in production}';

    protected $description = 'Apply or roll back source-controlled RAON Agent Factory Q&A guidance';

    public function __construct(private readonly PostService $postService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $fixture = $this->loadFixture();
            $board = $this->resolveBoard($fixture);
            $existing = $this->indexedProvenancePosts($board, $fixture);
            $this->assertNoDuplicates($existing, $fixture);

            if ($this->option('rollback')) {
                return $this->rollback($board, $fixture, $existing);
            }

            $author = $this->resolveAuthor();
            $plan = $this->buildPlan($fixture, $existing);

            $this->line(sprintf(
                '%s board=%s(%d) author=%s(%d) questions=%d answers=%d create=%d update=%d restore=%d unchanged=%d duplicates=0',
                $this->option('dry-run') ? '[dry-run]' : '[apply]',
                $board->slug,
                $board->id,
                $author->name,
                $author->id,
                count($fixture['scenarios']),
                count($fixture['scenarios']),
                $plan['create'],
                $plan['update'],
                $plan['restore'],
                $plan['unchanged'],
            ));

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            $this->guardProductionWrite();
            $stats = $this->apply($board, $author, $fixture, $existing);
            $this->info(sprintf(
                'Q&A content applied: created=%d updated=%d restored=%d unchanged=%d duplicates=0',
                $stats['created'],
                $stats['updated'],
                $stats['restored'],
                $stats['unchanged'],
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /** @return array<string, mixed> */
    private function loadFixture(): array
    {
        $path = dirname(__DIR__, 3).'/database/content/agent-factory-qa.v1.json';
        $fixture = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (($fixture['schema_version'] ?? null) !== 1 || count($fixture['scenarios'] ?? []) < 8) {
            throw new RuntimeException('Q&A fixture schema or minimum scenario count is invalid.');
        }
        if (($fixture['provenance']['kind'] ?? null) !== 'synthetic_operational_guidance') {
            throw new RuntimeException('Q&A fixture must declare synthetic operational-guidance provenance.');
        }

        foreach ($fixture['scenarios'] as $scenario) {
            foreach (['key', 'sort_order', 'question', 'answer', 'basis'] as $required) {
                if (! array_key_exists($required, $scenario)) {
                    throw new RuntimeException("Q&A scenario is missing {$required}.");
                }
            }
            foreach (['question', 'answer'] as $role) {
                if (empty($scenario[$role]['title']) || empty($scenario[$role]['content'])) {
                    throw new RuntimeException("Q&A scenario {$scenario['key']} has empty {$role} content.");
                }
            }
        }

        return $fixture;
    }

    /** @param array<string, mixed> $fixture */
    private function resolveBoard(array $fixture): Board
    {
        $slug = (string) $this->option('board');
        $board = Board::query()->where('slug', $slug)->first();

        if (! $board || ! $board->is_active) {
            throw new RuntimeException("Active board '{$slug}' was not found.");
        }
        if (($fixture['board']['requires_reply'] ?? false) && ! $board->use_reply) {
            throw new RuntimeException("Board '{$slug}' does not enable post replies.");
        }

        $category = $fixture['board']['category'] ?? null;
        if ($category !== null && ! in_array($category, $board->categories ?? [], true)) {
            throw new RuntimeException("Board '{$slug}' does not declare category '{$category}'.");
        }

        return $board;
    }

    private function resolveAuthor(): User
    {
        $query = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereNull('withdrawn_at')
            ->superAdmins();

        if ($uuid = $this->option('author')) {
            $query->where('uuid', $uuid);
        }

        $author = $query->orderBy('id')->first();
        if (! $author) {
            throw new RuntimeException('An existing active super administrator is required; no user is created by this command.');
        }

        return $author;
    }

    /**
     * @param  array<string, mixed>  $fixture
     * @return array<string, Collection<int, Post>>
     */
    private function indexedProvenancePosts(Board $board, array $fixture): array
    {
        $key = (string) $fixture['provenance']['key'];
        $posts = Post::withTrashed()
            ->where('board_id', $board->id)
            ->where('action_logs', 'like', '%'.$key.'%')
            ->get();

        $indexed = [];
        foreach ($posts as $post) {
            foreach ($post->action_logs ?? [] as $log) {
                if (($log['provenance_key'] ?? null) !== $key) {
                    continue;
                }
                $index = ($log['scenario_key'] ?? '').':'.($log['content_role'] ?? '');
                $indexed[$index] ??= collect();
                $indexed[$index]->push($post);
                break;
            }
        }

        return $indexed;
    }

    /** @param array<string, Collection<int, Post>> $existing @param array<string, mixed> $fixture */
    private function assertNoDuplicates(array $existing, array $fixture): void
    {
        foreach ($fixture['scenarios'] as $scenario) {
            foreach (['question', 'answer'] as $role) {
                $index = $scenario['key'].':'.$role;
                if (($existing[$index] ?? collect())->count() > 1) {
                    throw new RuntimeException("Duplicate provenance rows found for {$index}; resolve them before applying.");
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fixture
     * @param  array<string, Collection<int, Post>>  $existing
     * @return array{create:int, update:int, restore:int, unchanged:int}
     */
    private function buildPlan(array $fixture, array $existing): array
    {
        $plan = ['create' => 0, 'update' => 0, 'restore' => 0, 'unchanged' => 0];
        foreach ($fixture['scenarios'] as $scenario) {
            foreach (['question', 'answer'] as $role) {
                $post = ($existing[$scenario['key'].':'.$role] ?? collect())->first();
                if (! $post) {
                    $plan['create']++;
                } elseif ($post->trashed()) {
                    $plan['restore']++;
                } elseif ($this->contentDiffers($post, $scenario, $role, $fixture)) {
                    $plan['update']++;
                } else {
                    $plan['unchanged']++;
                }
            }
        }

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $fixture
     * @param  array<string, Collection<int, Post>>  $existing
     * @return array{created:int, updated:int, restored:int, unchanged:int}
     */
    private function apply(Board $board, User $author, array $fixture, array $existing): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'restored' => 0, 'unchanged' => 0];

        foreach (collect($fixture['scenarios'])->sortByDesc('sort_order') as $scenario) {
            $question = $this->upsertPost($board, $author, $scenario, 'question', null, $fixture, $existing, $stats);
            $this->upsertPost($board, $author, $scenario, 'answer', $question->id, $fixture, $existing, $stats);
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  array<string, mixed>  $fixture
     * @param  array<string, Collection<int, Post>>  $existing
     * @param  array{created:int, updated:int, restored:int, unchanged:int}  $stats
     */
    private function upsertPost(
        Board $board,
        User $author,
        array $scenario,
        string $role,
        ?int $parentId,
        array $fixture,
        array $existing,
        array &$stats,
    ): Post {
        $index = $scenario['key'].':'.$role;
        /** @var Post|null $post */
        $post = ($existing[$index] ?? collect())->first();

        if ($post?->trashed()) {
            if ($role === 'question') {
                $this->postService->restorePost($board->slug, $post->id, 'Source-controlled Q&A content reapplied', TriggerType::System->value);
            }
            $post->refresh();
            if ($post->trashed()) {
                $this->postService->restorePost($board->slug, $post->id, 'Source-controlled Q&A content reapplied', TriggerType::System->value);
                $post->refresh();
            }
            $stats['restored']++;
        }

        $data = $this->postData($author, $scenario, $role, $parentId, $fixture, $post);
        if (! $post) {
            $post = $this->postService->createPost(
                $board->slug,
                $data,
                options: ['skip_notification' => true],
            );
            $stats['created']++;

            return $post;
        }

        if ($post->status !== PostStatus::Published) {
            throw new RuntimeException("Provenance post {$post->id} is moderated as {$post->status->value}; refusing to overwrite it.");
        }

        if ($this->contentDiffers($post, $scenario, $role, $fixture, $parentId, $author->id)) {
            $post = $this->postService->updatePost($board->slug, $post->id, $data);
            $stats['updated']++;
        } elseif (! $post->wasRecentlyCreated && ! $post->trashed()) {
            $stats['unchanged']++;
        }

        return $post;
    }

    /** @param array<string, mixed> $scenario @param array<string, mixed> $fixture @return array<string, mixed> */
    private function postData(User $author, array $scenario, string $role, ?int $parentId, array $fixture, ?Post $post): array
    {
        return [
            'category' => $fixture['board']['category'] ?? null,
            'title' => $scenario[$role]['title'],
            'content' => $scenario[$role]['content'],
            'content_mode' => 'html',
            'user_id' => $author->id,
            'author_name' => null,
            'ip_address' => '127.0.0.1',
            'is_notice' => false,
            'is_secret' => false,
            'status' => PostStatus::Published->value,
            'trigger_type' => TriggerType::System->value,
            'parent_id' => $parentId,
            'action_logs' => $this->provenanceLogs($post, $author, $scenario, $role, $fixture),
        ];
    }

    /** @param array<string, mixed> $scenario @param array<string, mixed> $fixture @return array<int, array<string, mixed>> */
    private function provenanceLogs(?Post $post, User $author, array $scenario, string $role, array $fixture): array
    {
        $logs = collect($post?->action_logs ?? []);
        $key = (string) $fixture['provenance']['key'];
        $old = $logs->first(fn (array $log): bool => ($log['provenance_key'] ?? null) === $key
            && ($log['scenario_key'] ?? null) === $scenario['key']
            && ($log['content_role'] ?? null) === $role);

        $logs = $logs->reject(fn (array $log): bool => ($log['provenance_key'] ?? null) === $key
            && ($log['scenario_key'] ?? null) === $scenario['key']
            && ($log['content_role'] ?? null) === $role);

        $logs->prepend([
            'action' => 'content_seed',
            'reason' => 'Source-controlled synthetic Q&A operational guidance',
            'admin_id' => $author->id,
            'admin_name' => $author->name,
            'created_at' => $old['created_at'] ?? now()->toIso8601String(),
            'provenance_key' => $key,
            'provenance_kind' => $fixture['provenance']['kind'],
            'fixture_version' => $fixture['fixture_version'],
            'scenario_key' => $scenario['key'],
            'content_role' => $role,
            'content_sha256' => hash('sha256', $scenario[$role]['title']."\n".$scenario[$role]['content']),
            'basis' => $scenario['basis'],
        ]);

        return $logs->values()->all();
    }

    /** @param array<string, mixed> $scenario @param array<string, mixed> $fixture */
    private function contentDiffers(
        Post $post,
        array $scenario,
        string $role,
        array $fixture,
        ?int $parentId = null,
        ?int $authorId = null,
    ): bool {
        $expectedHash = hash('sha256', $scenario[$role]['title']."\n".$scenario[$role]['content']);
        $marker = collect($post->action_logs ?? [])->first(fn (array $log): bool => ($log['provenance_key'] ?? null) === $fixture['provenance']['key']
            && ($log['scenario_key'] ?? null) === $scenario['key']
            && ($log['content_role'] ?? null) === $role
        );

        return $post->title !== $scenario[$role]['title']
            || $post->content !== $scenario[$role]['content']
            || $post->content_mode !== 'html'
            || $post->category !== ($fixture['board']['category'] ?? null)
            || ($parentId !== null && (int) $post->parent_id !== $parentId)
            || ($authorId !== null && (int) $post->user_id !== $authorId)
            || ($marker['fixture_version'] ?? null) !== $fixture['fixture_version']
            || ($marker['content_sha256'] ?? null) !== $expectedHash;
    }

    /** @param array<string, mixed> $fixture @param array<string, Collection<int, Post>> $existing */
    private function rollback(Board $board, array $fixture, array $existing): int
    {
        $roots = collect($fixture['scenarios'])
            ->map(fn (array $scenario) => ($existing[$scenario['key'].':question'] ?? collect())->first())
            ->filter(fn (?Post $post): bool => $post !== null && ! $post->trashed());
        $activeRows = collect($existing)->flatten()->filter(fn (Post $post): bool => ! $post->trashed())->count();

        $this->line(sprintf(
            '%s board=%s(%d) roots=%d rows=%d provenance=%s',
            $this->option('dry-run') ? '[dry-run rollback]' : '[rollback]',
            $board->slug,
            $board->id,
            $roots->count(),
            $activeRows,
            $fixture['provenance']['key'],
        ));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $this->guardProductionWrite();
        foreach ($roots as $post) {
            $this->postService->deletePost(
                $board->slug,
                $post->id,
                TriggerType::System->value,
                ['skip_notification' => true, 'cascade_replies' => true],
            );
        }

        $this->info("Q&A content rolled back: roots={$roots->count()} rows={$activeRows}");

        return self::SUCCESS;
    }

    private function guardProductionWrite(): void
    {
        if (app()->environment('production') && ! $this->option('force')) {
            throw new RuntimeException('Production writes require --force after integration review.');
        }
    }
}
