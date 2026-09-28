<?php

namespace Modules\Raonslab\Product\Adapters;

use Illuminate\Support\Facades\DB;
use Modules\Raonslab\Product\Contracts\ConsultationBoardGateway;
use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Exceptions\IdempotencyConflictException;
use Modules\Raonslab\Product\Services\ConsultationBoardProvisioner;
use Modules\Raonslab\Product\Services\ConsultationSubmissionResult;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Repositories\Contracts\BoardRepositoryInterface;
use Modules\Sirsoft\Board\Repositories\Contracts\PostRepositoryInterface;
use Modules\Sirsoft\Board\Services\PostService;
use RuntimeException;

class G7BoardConsultationAdapter implements ConsultationBoardGateway
{
    public function __construct(
        private ConsultationBoardProvisioner $provisioner,
        private BoardRepositoryInterface $boards,
        private PostRepositoryInterface $posts,
        private PostService $postService,
    ) {}

    public function persist(
        string $reference,
        string $payloadHash,
        array $content,
        string $ipAddress,
        ConsultationStatus $status,
    ): ConsultationSubmissionResult {
        $board = $this->provisioner->ensureReady();

        return DB::transaction(function () use ($board, $reference, $payloadHash, $content, $ipAddress, $status) {
            $this->boards->query()->whereKey($board->id)->lockForUpdate()->firstOrFail();

            $existing = $this->findByReference($board->slug, $reference);
            if ($existing !== null) {
                $stored = $this->decode($existing);
                if (! hash_equals((string) ($stored['payload_hash'] ?? ''), $payloadHash)) {
                    throw new IdempotencyConflictException;
                }

                return new ConsultationSubmissionResult($existing, false, $reference);
            }

            $post = $this->postService->createPost($board->slug, [
                'title' => $reference,
                'content' => json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'content_mode' => 'text',
                'category' => $status->value,
                'author_name' => 'Guest consultation',
                'ip_address' => $ipAddress,
                'is_notice' => false,
                'is_secret' => true,
                'trigger_type' => 'system',
                'user_id' => null,
            ], options: ['skip_notification' => true]);

            $persisted = $this->posts->find($board->slug, $post->id);
            if (! $persisted instanceof Post
                || $persisted->title !== $reference
                || ! $persisted->is_secret
                || $persisted->board_id !== $board->id) {
                throw new RuntimeException(__('common.failed'));
            }

            return new ConsultationSubmissionResult($persisted, true, $reference);
        }, 3);
    }

    private function findByReference(string $slug, string $reference): ?Post
    {
        for ($pageNumber = 1; ; $pageNumber++) {
            $page = $this->posts->paginate($slug, ['page' => $pageNumber], 100);

            foreach ($page->items() as $candidate) {
                if ($candidate->title === $reference) {
                    return $this->posts->find($slug, $candidate->id);
                }
            }

            if (count($page->items()) < 100) {
                return null;
            }
        }
    }

    /** @return array<string, mixed> */
    private function decode(Post $post): array
    {
        $decoded = json_decode((string) $post->content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
