<?php

namespace Modules\Raonslab\TravelLab\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\PermissionType;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Modules\Raonslab\TravelLab\Exceptions\TravelCampaignException;
use Modules\Raonslab\TravelLab\Support\CampaignRegistry;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Repositories\Contracts\PageRepositoryInterface;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * LAB 캠페인 Page 두 건을 명시 실행으로만 준비합니다.
 *
 * 안전 계약:
 * - 설정 `raonslab-travel_lab.campaigns.lab_provisioning`(격리 표식 + 캠페인 전용 플래그, 기본 false),
 *   호출자의 --lab-confirm, 로컬 부작용 구성(메일 array·큐 sync·로컬 저장소·mysql-fulltext),
 *   native Page read/create 권한을 가진 실존 관리자 actor 를 모두 확인한 뒤에만 쓴다.
 * - 같은 slug 의 Page 가 있으면(초안·운영자 편집본 포함) 절대 수정하지 않고 건너뛴다.
 * - 없는 슬롯만 native PageService::createPage 로 만든다 — 버전 1 스냅샷·활동 로그·SEO/사이트맵
 *   리스너가 native 그대로 실행된다. Page 테이블 직접 쓰기는 없다.
 * - actor 는 이 실행 범위에서만 기본 guard 에 설정되고, 끝나면 이전 상태로 복원된다. 토큰은 만들지 않는다.
 */
class TravelCampaignProvisioner
{
    /** 프로비저닝이 요구하는 로컬 부작용 구성 */
    public const REQUIRED_ENVIRONMENT = [
        'mail.default' => 'array',
        'queue.default' => 'sync',
        'scout.driver' => 'mysql-fulltext',
    ];

    public function __construct(
        private CampaignRegistry $registry,
        private PageService $pageService,
        private PageRepositoryInterface $pages,
        private UserRepositoryInterface $users,
    ) {}

    public function isLabProvisioningEnabled(): bool
    {
        return config('raonslab-travel_lab.campaigns.lab_provisioning') === true;
    }

    /**
     * @param  bool  $labConfirmed  --lab-confirm 명시 여부
     * @param  int|null  $actorId  --actor 로 넘긴 관리자 사용자 ID
     * @return array{created: list<array{slug: string, id: int}>, skipped: list<array{slug: string, id: int}>}
     *
     * @throws TravelCampaignException 어떤 가드라도 실패하면 쓰기 전에 던진다
     */
    public function provision(bool $labConfirmed, ?int $actorId): array
    {
        if (! $labConfirmed || ! $this->isLabProvisioningEnabled()) {
            throw TravelCampaignException::notAllowed();
        }
        $this->assertLocalEnvironment();
        $actor = $this->resolveActor($actorId);

        $guard = Auth::guard();
        $previous = $guard->hasUser() ? $guard->user() : null;
        $guard->setUser($actor);

        try {
            $report = ['created' => [], 'skipped' => []];
            foreach ($this->registry->all() as $slot) {
                $existing = $this->pages->findBySlug($slot->slug);
                if ($existing instanceof Page) {
                    $report['skipped'][] = ['slug' => $slot->slug, 'id' => (int) $existing->id];

                    continue;
                }

                $seed = TravelCampaignSeedContent::for($slot->key)
                    ?? throw new \LogicException('Missing synthetic campaign content for '.$slot->key);
                try {
                    $page = $this->pageService->createPage([
                        'slug' => $slot->slug,
                        'title' => $seed['title'],
                        'content' => $seed['content'],
                        'content_mode' => 'text',
                        'published' => true,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // 확인과 생성 사이에 같은 slug 가 생겼다 — 덮어쓰지 않고 드러낸다.
                    throw TravelCampaignException::slugConflict($slot->slug, $e);
                }
                $report['created'][] = ['slug' => $slot->slug, 'id' => (int) $page->id];
            }

            return $report;
        } finally {
            if ($previous !== null) {
                $guard->setUser($previous);
            } else {
                $guard->forgetUser();
            }
        }
    }

    private function assertLocalEnvironment(): void
    {
        foreach (self::REQUIRED_ENVIRONMENT as $key => $expected) {
            $actual = (string) config($key);
            if ($actual !== $expected) {
                throw TravelCampaignException::unsafeEnvironment($key, $actual, $expected);
            }
        }

        $disk = (string) config('filesystems.default');
        $driver = (string) config("filesystems.disks.{$disk}.driver");
        if ($driver !== 'local') {
            throw TravelCampaignException::unsafeEnvironment('filesystems.default', $driver, 'local');
        }
    }

    private function resolveActor(?int $actorId): User
    {
        if ($actorId === null || $actorId < 1) {
            throw TravelCampaignException::actorInvalid();
        }

        $actor = $this->users->findById($actorId);
        if (! $actor instanceof User) {
            throw TravelCampaignException::actorInvalid();
        }

        foreach (['sirsoft-page.pages.read', 'sirsoft-page.pages.create'] as $permission) {
            if (! $actor->hasPermission($permission, PermissionType::Admin)) {
                throw TravelCampaignException::actorNotPermitted($actorId);
            }
        }

        return $actor;
    }
}
