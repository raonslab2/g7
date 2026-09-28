<?php

namespace Modules\Raonslab\Product\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Sirsoft\Board\Enums\PostStatus;
use Modules\Sirsoft\Board\Enums\ReportType;
use Modules\Sirsoft\Board\Enums\TriggerType;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Models\Report;
use Modules\Sirsoft\Board\Services\PostService;
use RuntimeException;

class SeedQaContentCommand extends Command
{
    private const LOCK_SECONDS = 300;

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
            if ($this->option('dry-run')) {
                return $this->executeOperation($board, $fixture, false);
            }

            $this->guardProductionWrite();
            $lock = Cache::lock(self::lockName((string) $fixture['provenance']['key']), self::LOCK_SECONDS);
            if (! $lock->get()) {
                throw new RuntimeException('Another Q&A provenance operation is already running; no rows were changed.');
            }

            try {
                return $this->executeOperation($board, $fixture, true);
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /** @internal Stable only for identifying the distributed operation lock in focused tests. */
    public static function lockName(string $provenanceKey): string
    {
        return 'raonslab-product:qa-content:'.hash('sha256', $provenanceKey);
    }

    /** @param array<string, mixed> $fixture */
    private function executeOperation(Board $board, array $fixture, bool $mutate): int
    {
        $existing = $this->indexedProvenancePosts($board, $fixture);
        $this->assertNoDuplicates($existing, $fixture);
        $this->assertProvenanceTopology($existing, $fixture);

        if ($this->option('rollback')) {
            return $this->rollback($board, $fixture, $existing, $mutate);
        }

        $author = $this->resolveAuthor();
        $plan = $this->buildPlan($fixture, $existing);

        $this->line(sprintf(
            '%s board=%s(%d) author=%s(%d) questions=%d answers=%d create=%d update=%d restore=%d unchanged=%d duplicates=0',
            $mutate ? '[apply]' : '[dry-run]',
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

        if (! $mutate) {
            return self::SUCCESS;
        }

        $stats = DB::transaction(function () use ($board, $author, $fixture): array {
            $fresh = $this->indexedProvenancePosts($board, $fixture, true);
            $this->assertNoDuplicates($fresh, $fixture);
            $this->assertProvenanceTopology($fresh, $fixture);

            return $this->apply($board, $author, $fixture, $fresh);
        });
        $this->info(sprintf(
            'Q&A content applied: created=%d updated=%d restored=%d unchanged=%d duplicates=0',
            $stats['created'],
            $stats['updated'],
            $stats['restored'],
            $stats['unchanged'],
        ));

        return self::SUCCESS;
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
    private function indexedProvenancePosts(Board $board, array $fixture, bool $forUpdate = false): array
    {
        $key = (string) $fixture['provenance']['key'];
        $expectedIndexes = collect($fixture['scenarios'])->flatMap(fn (array $scenario): array => [
            $scenario['key'].':question',
            $scenario['key'].':answer',
        ])->flip();
        $query = Post::withTrashed()
            ->where('board_id', $board->id)
            ->where('action_logs', 'like', '%'.$key.'%');
        if ($forUpdate) {
            $query->lockForUpdate();
        }
        $posts = $query->get();

        $indexed = [];
        foreach ($posts as $post) {
            $markers = collect($post->action_logs ?? [])->filter(
                fn (array $log): bool => ($log['provenance_key'] ?? null) === $key
            );
            if ($markers->count() !== 1) {
                throw new RuntimeException("Provenance post {$post->id} must carry exactly one marker for {$key}.");
            }

            $marker = $markers->first();
            $index = ($marker['scenario_key'] ?? '').':'.($marker['content_role'] ?? '');
            if (! $expectedIndexes->has($index)
                || ($marker['provenance_kind'] ?? null) !== $fixture['provenance']['kind']) {
                throw new RuntimeException("Provenance post {$post->id} has an invalid scenario, role, or kind.");
            }

            $indexed[$index] ??= collect();
            $indexed[$index]->push($post);
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

    /** @param array<string, Collection<int, Post>> $existing @param array<string, mixed> $fixture */
    private function assertProvenanceTopology(array $existing, array $fixture): void
    {
        foreach ($fixture['scenarios'] as $scenario) {
            /** @var Post|null $question */
            $question = ($existing[$scenario['key'].':question'] ?? collect())->first();
            /** @var Post|null $answer */
            $answer = ($existing[$scenario['key'].':answer'] ?? collect())->first();

            if ($question && ($question->parent_id !== null || (int) $question->depth !== 0)) {
                throw new RuntimeException("Topology mismatch for question {$question->id}; expected parent_id=null and depth=0.");
            }
            if ($answer && ! $question) {
                throw new RuntimeException("Topology mismatch for answer {$answer->id}; its provenance question is missing.");
            }
            if ($answer && ((int) $answer->parent_id !== (int) $question?->id || (int) $answer->depth !== 1)) {
                throw new RuntimeException("Topology mismatch for answer {$answer->id}; expected its provenance question parent and depth=1.");
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
            $this->assertWrittenTopology($post, $role, $parentId);
            $stats['created']++;

            return $post;
        }

        if ($post->status !== PostStatus::Published) {
            throw new RuntimeException("Provenance post {$post->id} is moderated as {$post->status->value}; refusing to overwrite it.");
        }

        if ($this->contentDiffers($post, $scenario, $role, $fixture, $parentId, $author->id)) {
            $post = $this->postService->updatePost($board->slug, $post->id, $data);
            $this->assertWrittenTopology($post, $role, $parentId);
            $stats['updated']++;
        } elseif (! $post->wasRecentlyCreated && ! $post->trashed()) {
            $stats['unchanged']++;
        }

        return $post;
    }

    private function assertWrittenTopology(Post $post, string $role, ?int $parentId): void
    {
        $valid = $role === 'question'
            ? $post->parent_id === null && (int) $post->depth === 0
            : (int) $post->parent_id === (int) $parentId && (int) $post->depth === 1;

        if (! $valid) {
            throw new RuntimeException("PostService returned invalid {$role} topology for post {$post->id}.");
        }
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
    private function rollback(Board $board, array $fixture, array $existing, bool $mutate): int
    {
        if (! $mutate) {
            $this->assertRollbackSafe($existing, $fixture);
            $roots = $this->activeRolePosts($existing, $fixture, 'question');
            $activeRows = collect($existing)->flatten()->filter(fn (Post $post): bool => ! $post->trashed())->count();

            $this->line(sprintf(
                '[dry-run rollback] board=%s(%d) roots=%d rows=%d provenance=%s',
                $board->slug,
                $board->id,
                $roots->count(),
                $activeRows,
                $fixture['provenance']['key'],
            ));

            return self::SUCCESS;
        }

        $stats = DB::transaction(function () use ($board, $fixture): array {
            $fresh = $this->indexedProvenancePosts($board, $fixture, true);
            $this->assertNoDuplicates($fresh, $fixture);
            $this->assertProvenanceTopology($fresh, $fixture);
            $this->assertRollbackSafe($fresh, $fixture, true);

            $answers = $this->activeRolePosts($fresh, $fixture, 'answer');
            $roots = $this->activeRolePosts($fresh, $fixture, 'question');
            $activeRows = collect($fresh)->flatten()->filter(fn (Post $post): bool => ! $post->trashed())->count();

            foreach ($answers->concat($roots) as $post) {
                $this->postService->deletePost(
                    $board->slug,
                    $post->id,
                    TriggerType::System->value,
                    ['skip_notification' => true, 'cascade_replies' => false],
                );
            }

            return ['roots' => $roots->count(), 'rows' => $activeRows];
        });

        $this->line(sprintf(
            '[rollback] board=%s(%d) roots=%d rows=%d provenance=%s',
            $board->slug,
            $board->id,
            $stats['roots'],
            $stats['rows'],
            $fixture['provenance']['key'],
        ));
        $this->info("Q&A content rolled back: roots={$stats['roots']} rows={$stats['rows']}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, Collection<int, Post>>  $existing
     * @param  array<string, mixed>  $fixture
     * @return Collection<int, Post>
     */
    private function activeRolePosts(array $existing, array $fixture, string $role): Collection
    {
        return collect($fixture['scenarios'])
            ->map(fn (array $scenario) => ($existing[$scenario['key'].':'.$role] ?? collect())->first())
            ->filter(fn (?Post $post): bool => $post !== null && ! $post->trashed())
            ->values();
    }

    /** @param array<string, Collection<int, Post>> $existing @param array<string, mixed> $fixture */
    private function assertRollbackSafe(array $existing, array $fixture, bool $forUpdate = false): void
    {
        $owned = collect($existing)->flatten()->keyBy(fn (Post $post): int => (int) $post->id);

        foreach ($fixture['scenarios'] as $scenario) {
            /** @var Post|null $question */
            $question = ($existing[$scenario['key'].':question'] ?? collect())->first();
            /** @var Post|null $answer */
            $answer = ($existing[$scenario['key'].':answer'] ?? collect())->first();

            if ($question) {
                $descendants = $this->descendantsUsingOfficialRelation($question, $forUpdate);
                $allowed = $answer ? [(int) $answer->id] : [];
                $external = $descendants->reject(fn (Post $post): bool => in_array((int) $post->id, $allowed, true));
                if ($external->isNotEmpty()) {
                    throw new RuntimeException('Rollback blocked by non-owned descendant post(s): '.$external->pluck('id')->join(', ').'. No rows were changed.');
                }
            }

            if ($answer && $this->descendantsUsingOfficialRelation($answer, $forUpdate)->isNotEmpty()) {
                throw new RuntimeException("Rollback blocked by descendant(s) of owned answer {$answer->id}. No rows were changed.");
            }
        }

        foreach ($owned as $post) {
            $comments = $post->comments()->withTrashed();
            $attachments = $post->attachments()->withTrashed();
            if ($forUpdate) {
                $comments->lockForUpdate();
                $attachments->lockForUpdate();
            }
            $commentIds = $comments->pluck('id');
            $attachmentIds = $attachments->pluck('id');
            if ($commentIds->isNotEmpty()) {
                throw new RuntimeException("Rollback blocked by comment(s) on post {$post->id}: ".$commentIds->join(', ').'. No rows were changed.');
            }
            if ($attachmentIds->isNotEmpty()) {
                throw new RuntimeException("Rollback blocked by attachment(s) on post {$post->id}: ".$attachmentIds->join(', ').'. No rows were changed.');
            }
        }

        if ($owned->isNotEmpty()) {
            $reports = Report::withTrashed()
                ->where('board_id', $owned->first()->board_id)
                ->where('target_type', ReportType::Post->value)
                ->whereIn('target_id', $owned->keys());
            if ($forUpdate) {
                $reports->lockForUpdate();
            }
            $reportIds = $reports->pluck('id');
            if ($reportIds->isNotEmpty()) {
                throw new RuntimeException('Rollback blocked by report interaction(s): '.$reportIds->join(', ').'. No rows were changed.');
            }
        }
    }

    /** @return Collection<int, Post> */
    private function descendantsUsingOfficialRelation(Post $root, bool $forUpdate): Collection
    {
        $descendants = collect();
        $pending = collect([$root]);
        $seen = [(int) $root->id => true];

        while ($pending->isNotEmpty()) {
            /** @var Post $parent */
            $parent = $pending->shift();
            $children = $parent->replies()->withTrashed()->where('board_id', $root->board_id);
            if ($forUpdate) {
                $children->lockForUpdate();
            }

            foreach ($children->get() as $child) {
                if (isset($seen[(int) $child->id])) {
                    continue;
                }
                $seen[(int) $child->id] = true;
                $descendants->push($child);
                $pending->push($child);
            }
        }

        return $descendants;
    }

    private function guardProductionWrite(): void
    {
        if (app()->environment('production') && ! $this->option('force')) {
            throw new RuntimeException('Production writes require --force after integration review.');
        }
    }
}
