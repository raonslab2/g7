<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

require_once __DIR__.'/../SupportTestCase.php';

use App\Extension\HookListenerRegistrar;
use App\Extension\HookManager;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\CollectionEngine;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Raonslab\TravelLab\Exceptions\TravelSupportException;
use Modules\Raonslab\TravelLab\Services\TravelSupportProvisioner;
use Modules\Raonslab\TravelLab\Tests\SupportTestCase;
use Modules\Sirsoft\Board\Listeners\BoardActivityLogListener;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Services\PostService;
use PHPUnit\Framework\Attributes\Test;

/**
 * W03 고객지원 보안 보강 회귀 테스트 (실제 게시판 모듈 서비스·테이블·권한, 가짜 어댑터 없음).
 *
 * @scenario case=legacy_support_role_foreign_denied|board_admin_foreign_allowed|support_permission_required|board_scope_self_owner_only|tampered_board_fail_closed|native_update_audit|update_notifications_blocked|external_search_driver_closed|external_index_restore_removed
 */
class W03SupportHardeningTest extends SupportTestCase
{
    private const QUESTIONS = 'travel-lab-questions';

    protected function setUp(): void
    {
        parent::setUp();
        $this->provisionSupport();
    }

    /**
     * 전역 역할(manager 등)에 여행 support.read/update 만 남아 있어도 남의 비밀 문의는 닫힌다.
     *
     * @scenario case=legacy_support_role_foreign_denied
     *
     * @effects foreign_list_hidden, foreign_detail_404, foreign_patch_404, foreign_row_unchanged, own_question_still_open
     */
    #[Test]
    public function legacy_support_role_without_native_board_admin_cannot_reach_foreign_questions(): void
    {
        $alice = $this->createMember();
        $aliceId = $this->ask($alice, '[합성] 앨리스 비밀 문의');

        $legacy = $this->userWithRole('manager', [
            'admin.access',
            'raonslab-travel_lab.support.read',
            'raonslab-travel_lab.support.update',
        ]);
        $legacyOwnId = $this->ask($legacy, '[합성] 매니저 본인 문의');

        $list = $this->actingAs($legacy)->getJson(self::API.'/support/questions')->assertOk()
            ->assertJsonPath('data.meta.total', 1);
        $this->assertSame([$legacyOwnId], array_column($list->json('data.data'), 'id'));

        $this->actingAs($legacy)->getJson(self::API."/support/questions/{$aliceId}")
            ->assertNotFound()
            ->assertJsonPath('message', __('raonslab-travel_lab::support.errors.question_not_found'));
        $this->actingAs($legacy)->patchJson(self::API."/support/questions/{$aliceId}", ['title' => '탈취 시도'])
            ->assertNotFound();
        $this->assertSame('[합성] 앨리스 비밀 문의', Post::findOrFail($aliceId)->title);

        $this->actingAs($legacy)->getJson(self::API."/support/questions/{$legacyOwnId}")->assertOk();
        $this->actingAs($legacy)->patchJson(self::API."/support/questions/{$legacyOwnId}", ['title' => '[합성] 본인 수정'])
            ->assertOk()->assertJsonPath('data.title', '[합성] 본인 수정');
    }

    /**
     * 네이티브 게시판 관리자(admin 역할)라도 여행 support 권한이 없으면 남의 문의는 닫힌다.
     * 두 권한을 모두 가지면 열린다.
     *
     * @scenario case=support_permission_required|board_admin_foreign_allowed
     *
     * @effects board_admin_without_support_404, both_permissions_read, read_only_cannot_patch, both_update_permissions_patch
     */
    #[Test]
    public function foreign_access_requires_both_support_permission_and_native_board_admin(): void
    {
        $alice = $this->createMember();
        $aliceId = $this->ask($alice, '[합성] 관리자 범위 확인');

        $boardOnly = $this->userWithRole('admin', ['admin.access']);
        $this->actingAs($boardOnly)->getJson(self::API.'/support/questions')->assertOk()
            ->assertJsonPath('data.meta.total', 0);
        $this->actingAs($boardOnly)->getJson(self::API."/support/questions/{$aliceId}")->assertNotFound();

        $reader = $this->createSupportAdmin(['raonslab-travel_lab.support.read']);
        $this->actingAs($reader)->getJson(self::API.'/support/questions')->assertOk()
            ->assertJsonPath('data.meta.total', 1);
        $this->actingAs($reader)->getJson(self::API."/support/questions/{$aliceId}")->assertOk();
        $this->actingAs($reader)->patchJson(self::API."/support/questions/{$aliceId}", ['title' => '읽기 전용'])
            ->assertNotFound();

        $updater = $this->createSupportAdmin(['raonslab-travel_lab.support.read', 'raonslab-travel_lab.support.update']);
        $this->actingAs($updater)->patchJson(self::API."/support/questions/{$aliceId}", ['title' => '[합성] 관리자 정리'])
            ->assertOk()->assertJsonPath('data.title', '[합성] 관리자 정리');
    }

    /**
     * 게시판 전용 manager 역할의 문의 열람 스코프가 self 면 목록·상세 모두 본인 글로 좁혀진다.
     *
     * @scenario case=board_scope_self_owner_only
     *
     * @effects self_scope_list_own_only, self_scope_foreign_404, self_scope_own_200
     */
    #[Test]
    public function self_scoped_native_board_manager_sees_only_own_questions(): void
    {
        $alice = $this->createMember();
        $aliceId = $this->ask($alice, '[합성] 스코프 대상');

        $managerRole = Role::where('identifier', 'sirsoft-board.'.self::QUESTIONS.'.manager')->firstOrFail();
        $readPermissionIds = Permission::whereIn('identifier', [
            'sirsoft-board.'.self::QUESTIONS.'.admin.posts.read',
            'sirsoft-board.'.self::QUESTIONS.'.admin.posts.read-secret',
        ])->pluck('id')->all();
        $this->assertCount(2, $readPermissionIds);
        foreach ($readPermissionIds as $permissionId) {
            $managerRole->permissions()->updateExistingPivot($permissionId, ['scope_type' => 'self']);
        }

        $scoped = $this->userWithRole('travel-support-scoped', ['admin.access', 'raonslab-travel_lab.support.read']);
        $scoped->roles()->attach($managerRole);
        $scoped = $scoped->fresh();
        $ownId = $this->ask($scoped, '[합성] 스코프 본인 문의');

        $list = $this->actingAs($scoped)->getJson(self::API.'/support/questions')->assertOk();
        $this->assertSame([$ownId], array_column($list->json('data.data'), 'id'));
        $this->actingAs($scoped)->getJson(self::API."/support/questions/{$aliceId}")->assertNotFound();
        $this->actingAs($scoped)->getJson(self::API."/support/questions/{$ownId}")->assertOk();
    }

    /**
     * 게시판 권한·댓글·답글 설정이 어긋나면 요청 경로는 503, 재프로비저닝은 409 로 닫히고
     * 운영자가 바꾼 값은 덮어쓰지 않는다.
     *
     * @scenario case=tampered_board_fail_closed
     *
     * @effects public_role_on_secret_read_503, empty_roles_503, use_reply_503, use_comment_503, provision_refuses_without_overwrite
     */
    #[Test]
    public function tampered_board_permissions_or_settings_fail_closed_without_overwrite(): void
    {
        $alice = $this->createMember();
        $permission = Permission::where('identifier', 'sirsoft-board.'.self::QUESTIONS.'.posts.read-secret')->firstOrFail();
        $userRole = Role::where('identifier', 'user')->firstOrFail();

        $permission->roles()->attach($userRole->id, ['granted_at' => now()]);
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertStatus(503)
            ->assertJsonPath('message', __('raonslab-travel_lab::support.errors.not_ready'));
        $this->assertProvisionRefused(self::QUESTIONS);
        $this->assertTrue($permission->roles()->where('roles.id', $userRole->id)->exists(), 'operator change must not be overwritten');
        $permission->roles()->detach($userRole->id);
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertOk();

        // 역할 0개 = 게시판 모듈 해석상 전체 허용
        $snapshot = $permission->roles()->pluck('roles.id')->all();
        $permission->roles()->detach();
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertStatus(503);
        $permission->roles()->attach($snapshot, ['granted_at' => now()]);
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertOk();

        $board = Board::where('slug', self::QUESTIONS)->sole();
        DB::table($board->getTable())->where('id', $board->id)->update(['use_reply' => true]);
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertStatus(503);
        $this->assertProvisionRefused(self::QUESTIONS);
        DB::table($board->getTable())->where('id', $board->id)->update(['use_reply' => false, 'use_comment' => false]);
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertStatus(503);
        $this->assertFalse((bool) Board::whereKey($board->id)->value('use_comment'), 'operator change must not be overwritten');

        $faqs = Permission::where('identifier', 'sirsoft-board.travel-lab-faqs.posts.write')->firstOrFail();
        $faqs->roles()->attach($userRole->id, ['granted_at' => now()]);
        $this->getJson(self::API.'/support/faqs')->assertStatus(503);
    }

    /**
     * 문의 수정은 게시판 PostService 를 거쳐 네이티브 활동 로그가 실제로 저장되고,
     * 외부 알림은 나가지 않는다.
     *
     * @scenario case=native_update_audit|update_notifications_blocked
     *
     * @effects after_update_hook_fired, activity_log_persisted, secret_preserved, notifications_not_sent, mail_not_sent
     */
    #[Test]
    public function question_edit_goes_through_native_post_service_audit_without_notifications(): void
    {
        Notification::fake();
        Mail::fake();
        HookListenerRegistrar::register(BoardActivityLogListener::class, 'sirsoft-board');

        $fired = [];
        HookManager::addAction('sirsoft-board.post.after_update', function ($post, $slug, $snapshot = null) use (&$fired) {
            $fired[] = [$post->id, $slug, $snapshot['title'] ?? null];
        });

        $alice = $this->createMember();
        $id = $this->ask($alice, '[합성] 감사 전 제목');

        $this->actingAs($alice)->patchJson(self::API."/support/questions/{$id}", ['title' => '[합성] 감사 후 제목'])
            ->assertOk()->assertJsonPath('data.title', '[합성] 감사 후 제목');

        $this->assertSame([[$id, self::QUESTIONS, '[합성] 감사 전 제목']], $fired);
        $this->assertTrue(Post::findOrFail($id)->is_secret);

        $log = ActivityLog::query()->where('action', 'post.update')
            ->where('loggable_type', (new Post)->getMorphClass())
            ->where('loggable_id', $id)
            ->sole();
        $this->assertSame((int) $alice->id, (int) $log->user_id);
        $this->assertSame('sirsoft-board::activity_log.description.post_update', $log->description_key);
        $this->assertStringContainsString('감사 후 제목', json_encode($log->changes, JSON_UNESCAPED_UNICODE));

        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    /**
     * 외부 검색 드라이버 구성에서는 문의 채널이 닫히고 프로비저닝도 쓰기 없이 거부된다.
     *
     * @scenario case=external_search_driver_closed
     *
     * @effects questions_503, public_channels_still_open, provision_409_no_writes
     */
    #[Test]
    public function external_search_driver_closes_question_channel_and_provisioning(): void
    {
        $alice = $this->createMember();
        $before = Post::count();

        config(['scout.driver' => 'meilisearch']);
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertStatus(503);
        $this->actingAs($alice)->postJson(self::API.'/support/questions', ['title' => '[합성] 차단', 'content' => '합성 본문'])
            ->assertStatus(503);
        $this->getJson(self::API.'/support/notices')->assertOk();

        try {
            app(TravelSupportProvisioner::class)->provision(labConfirmed: true);
            $this->fail('provisioning must refuse external search drivers');
        } catch (TravelSupportException $e) {
            $this->assertSame('raonslab-travel_lab::support.errors.search_engine_unsafe', $e->getMessageKey());
            $this->assertSame(409, $e->getStatus());
        }
        $this->assertSame($before, Post::count());
    }

    /**
     * 복원(Scout 강제 저장)은 index_should_update 필터를 우회하므로 after_restore 훅이 외부 색인에서
     * 다시 제거한다. scout:import 경로는 이 모듈이 막을 수 없다는 한계도 그대로 고정한다.
     *
     * @scenario case=external_index_restore_removed
     *
     * @effects create_not_indexed, restore_removed_after_native_index, import_limit_documented
     */
    #[Test]
    public function external_index_removal_on_restore_and_documented_import_limit(): void
    {
        $alice = $this->createMember();
        $id = $this->ask($alice, '[합성] 색인 경로 확인');

        $spy = new class extends CollectionEngine
        {
            /** @var array<int, array{0: string, 1: array<int, int>}> */
            public array $calls = [];

            public function update($models): void
            {
                $this->calls[] = ['update', $models->map->getKey()->map(fn ($key) => (int) $key)->values()->all()];
            }

            public function delete($models): void
            {
                $this->calls[] = ['delete', $models->map->getKey()->map(fn ($key) => (int) $key)->values()->all()];
            }
        };
        app(EngineManager::class)->extend('w03-spy', fn () => $spy);
        config(['scout.driver' => 'w03-spy']);

        // 저장(수정) 경로: 필터가 색인을 막는다.
        $question = Post::findOrFail($id);
        $question->update(['title' => '[합성] 색인 경로 수정']);
        $this->assertNotContains(['update', [$id]], $spy->calls);

        // 네이티브 삭제 → 복원. 복원은 Scout 가 강제 색인하지만 after_restore 훅이 즉시 제거한다.
        $posts = app(PostService::class);
        $posts->deletePost(self::QUESTIONS, $id, 'admin', ['skip_notification' => true]);
        $spy->calls = [];
        $posts->restorePost(self::QUESTIONS, $id, null, 'admin');
        $deletes = array_values(array_filter($spy->calls, fn ($call) => $call[0] === 'delete' && in_array($id, $call[1], true)));
        $this->assertNotEmpty($deletes, 'restored question must be removed from the external index');
        $this->assertSame('delete', end($spy->calls)[0], 'removal must be the final index operation');

        // 알려진 한계: scout:import(makeAllSearchable)는 게시판 훅이 없어 문의도 색인한다.
        // 이 모듈은 외부 드라이버 구성에서 문의 채널을 닫는 것으로만 대응한다(위 테스트).
        $spy->calls = [];
        Post::makeAllSearchable();
        $imported = array_merge(...array_map(fn ($call) => $call[0] === 'update' ? $call[1] : [], $spy->calls ?: [['noop', []]]));
        $this->assertContains($id, $imported, 'documented limit: import path is not protected by this module');
    }

    private function ask(User $author, string $title): int
    {
        return (int) $this->actingAs($author)->postJson(self::API.'/support/questions', [
            'title' => $title,
            'content' => '합성 테스트 문의입니다. 실제 예약이 아닙니다.',
        ])->assertCreated()->json('data.id');
    }

    /**
     * 지정 역할(없으면 생성)에 관리자 권한을 부여하고 그 역할만 가진 사용자를 만듭니다.
     *
     * @param  array<int, string>  $permissions
     */
    private function userWithRole(string $roleIdentifier, array $permissions): User
    {
        $role = Role::firstOrCreate(
            ['identifier' => $roleIdentifier],
            ['name' => ['ko' => $roleIdentifier, 'en' => $roleIdentifier]],
        );

        foreach ($permissions as $identifier) {
            $permission = Permission::firstOrCreate(
                ['identifier' => $identifier],
                ['name' => ['ko' => $identifier, 'en' => $identifier], 'type' => 'admin'],
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user->fresh();
    }

    private function assertProvisionRefused(string $slug): void
    {
        try {
            app(TravelSupportProvisioner::class)->provision(labConfirmed: true);
            $this->fail('provisioning must refuse a tampered board');
        } catch (TravelSupportException $e) {
            $this->assertSame('raonslab-travel_lab::support.errors.board_misconfigured', $e->getMessageKey());
            $this->assertSame(['slug' => $slug], $e->getMessageParams());
        }
        $this->assertSame(3, Board::whereIn('slug', TravelSupportChannel::boardSlugs())->count());
    }
}
