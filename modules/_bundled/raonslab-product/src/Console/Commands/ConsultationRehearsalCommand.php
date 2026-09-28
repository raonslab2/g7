<?php

namespace Modules\Raonslab\Product\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Exceptions\IdempotencyConflictException;
use Modules\Raonslab\Product\Services\ConsultationBoardProvisioner;
use Modules\Raonslab\Product\Services\ConsultationService;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Repositories\Contracts\PostRepositoryInterface;
use Modules\Sirsoft\Board\Services\PostService;
use Throwable;

/**
 * 비식별 합성 데이터로 상담 저장·재전송·충돌·관리자 상태 변경 경로를 리허설합니다.
 *
 * 공개 접수 게이트와 무관하게 서비스 계층을 직접 호출하며, 모든 쓰기는 하나의
 * 트랜잭션 안에서 수행한 뒤 반드시 롤백합니다. 같은 연결을 쓰는 큐·캐시·활동 로그도
 * 함께 롤백되며, 종료 전 잔여 행이 0인지 재확인합니다.
 */
class ConsultationRehearsalCommand extends Command
{
    protected $signature = 'raonslab-product:consultation-rehearsal';

    protected $description = 'Rehearse the private-board consultation path with synthetic data inside a rolled-back transaction';

    public function handle(
        ConsultationBoardProvisioner $provisioner,
        ConsultationService $service,
        PostRepositoryInterface $posts,
        PostService $postService,
    ): int {
        try {
            $board = $provisioner->requireReady();
            $before = $this->postCount($board->id);
            $results = [];

            DB::beginTransaction();
            try {
                $key = 'rehearsal-'.Str::uuid()->toString();
                $payload = $this->syntheticPayload();

                $first = $service->submit([...$payload, 'idempotency_key' => $key]);
                $stored = $posts->find($board->slug, $first->post->id);
                $results['stored_private'] = $first->created
                    && $stored instanceof Post
                    && $stored->is_secret
                    && $stored->board_id === $board->id
                    && $stored->category === ConsultationStatus::New->value
                    && str_starts_with($first->reference, 'RAON-');
                $results['search_excluded'] = ! $first->post->searchIndexShouldBeUpdated();

                $replay = $service->submit([...$payload, 'idempotency_key' => $key]);
                $results['replay_idempotent'] = ! $replay->created && $replay->post->id === $first->post->id;

                try {
                    $service->submit([...$payload, 'message' => 'Changed synthetic message.', 'idempotency_key' => $key]);
                    $results['conflict_rejected'] = false;
                } catch (IdempotencyConflictException) {
                    $results['conflict_rejected'] = true;
                }

                $postService->updatePost($board->slug, $first->post->id, ['category' => ConsultationStatus::Contacted->value]);
                $results['admin_status_update'] = $posts->find($board->slug, $first->post->id)?->category
                    === ConsultationStatus::Contacted->value;
            } finally {
                DB::rollBack();
            }

            $results['no_residue'] = $this->postCount($board->id) === $before;
        } catch (Throwable $e) {
            // 합성 데이터만 다루지만 내부 예외 원문은 출력하지 않습니다.
            $this->error('Rehearsal failed before completion: '.class_basename($e));

            return self::FAILURE;
        }

        $this->table(['check', 'status'], array_map(
            static fn (string $name, bool $ok): array => [$name, $ok ? 'PASS' : 'FAIL'],
            array_keys($results),
            $results,
        ));

        return in_array(false, $results, true) ? self::FAILURE : self::SUCCESS;
    }

    private function postCount(int $boardId): int
    {
        return Post::withTrashed()->where('board_id', $boardId)->count();
    }

    /** @return array<string, mixed> */
    private function syntheticPayload(): array
    {
        return [
            'contact_name' => 'Synthetic Rehearsal',
            'email' => 'rehearsal@example.invalid',
            'company' => null,
            'phone' => null,
            'service_interest' => 'pilot',
            'message' => 'Synthetic consultation rehearsal. Not a real customer.',
            'privacy_consent' => true,
            'privacy_consent_version' => 'synthetic-rehearsal',
        ];
    }
}
