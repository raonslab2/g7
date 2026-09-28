<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Extension\HookListenerRegistrar;
use App\Extension\HookManager;
use App\Extension\UpgradeContext;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\Jobs\MakeSearchable;
use Modules\Raonslab\Product\Listeners\ExcludeConsultationPostsFromSearch;
use Modules\Raonslab\Product\Module;
use Modules\Raonslab\Product\Services\ConsultationService;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Services\BoardService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class ConsultationP1ContractTest extends ModuleTestCase
{
    #[Test]
    public function consultation_posts_never_enqueue_scout_index_updates_for_any_driver(): void
    {
        $this->assertContains(ExcludeConsultationPostsFromSearch::class, (new Module)->getHookListeners());

        HookManager::addFilter(
            'sirsoft-board.search.post.index_should_update',
            fn (): bool => true,
            100,
        );
        HookListenerRegistrar::register(ExcludeConsultationPostsFromSearch::class, 'raonslab-product-p1-test');
        Queue::fake();
        config(['scout.queue' => true]);

        foreach (['mysql-fulltext', 'database', 'collection', 'algolia', 'meilisearch', 'typesense'] as $driver) {
            config(['scout.driver' => $driver]);
            $result = app(ConsultationService::class)->submit([
                ...$this->syntheticPayload([
                    'email' => "pii-never-index-{$driver}@example.test",
                    'message' => "Synthetic private payload for {$driver}.",
                ]),
                'idempotency_key' => "search-isolation-{$driver}",
            ]);

            $this->assertFalse($result->post->searchIndexShouldBeUpdated(), $driver);

            $result->post->forceFill(['category' => 'CONTACTED'])->save();
        }

        Queue::assertNotPushed(MakeSearchable::class);
    }

    #[Test]
    public function guest_submit_does_not_provision_a_missing_board(): void
    {
        $this->deleteConsultationBoard();
        $this->enableIntake();

        $this->postConsultation($this->syntheticPayload(), 'missing-board-submit')
            ->assertStatus(503);

        $this->assertFalse(Board::where('slug', 'raon-consultations')->exists());
    }

    #[Test]
    public function official_upgrade_step_provisions_the_board_and_surfaces_mismatch_failure(): void
    {
        $this->deleteConsultationBoard();

        $steps = (new Module)->upgrades();
        $this->assertArrayHasKey('0.2.2', $steps);

        $context = new UpgradeContext('0.2.1', '0.2.2', '0.2.2', 'extension-upgrade');
        $steps['0.2.2']->run($context);

        $board = Board::where('slug', 'raon-consultations')->sole();
        $this->assertFalse($board->is_active);
        $this->assertSame('always', $board->secret_mode->value);

        $board->forceFill(['is_active' => true])->save();

        $this->expectException(RuntimeException::class);
        $steps['0.2.2']->run($context);
    }

    private function deleteConsultationBoard(): void
    {
        $board = Board::where('slug', 'raon-consultations')->sole();
        app(BoardService::class)->deleteBoard($board->id);
    }
}
