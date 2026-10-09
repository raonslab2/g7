<?php

namespace Modules\Raonslab\TravelLab\Tests;

use App\Extension\HookListenerRegistrar;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Listeners\ExcludeTravelSupportQuestionsFromSearch;
use Modules\Raonslab\TravelLab\Listeners\SuppressTravelSupportNotifications;
use Modules\Raonslab\TravelLab\Repositories\Contracts\TravelSupportPostRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\TravelSupportPostRepository;
use Modules\Raonslab\TravelLab\Services\TravelSupportProvisioner;
use Modules\Sirsoft\Board\Providers\BoardServiceProvider;
use Tests\TestCase;

/*
 * 모듈 composer.json·provider 가 아직 통합되지 않은 상태에서도 고객지원 테스트가 독립 실행되도록
 * 이 모듈 네임스페이스 오토로더를 보강한다 (통합 후에는 composer PSR-4 가 먼저 해석한다).
 */
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Modules\\Raonslab\\TravelLab\\Tests\\' => __DIR__.'/',
        'Modules\\Raonslab\\TravelLab\\' => dirname(__DIR__).'/src/',
    ];
    foreach ($prefixes as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (is_file($file)) {
                require_once $file;
            }

            return;
        }
    }
});

/**
 * 고객지원(공지/FAQ/1:1 문의) 테스트 공통 부트스트랩.
 *
 * 코어 + 게시판 모듈 마이그레이션만 사용한다 (고객지원은 자기 테이블이 없다).
 * 등록 통합(provider·module.php)은 리드가 소유하므로, 여기서는 통합 후와 같은 형태로
 * 라우트·바인딩·번역 네임스페이스·훅 리스너를 직접 연결한다.
 */
abstract class SupportTestCase extends TestCase
{
    use RefreshDatabase;

    protected const API = '/api/modules/raonslab-travel_lab';

    /**
     * @return array<class-string, int>
     */
    protected function setUpTraits()
    {
        app('migrator')->path(base_path('modules/_bundled/sirsoft-board/database/migrations'));
        $this->refreshDatabase();

        return array_flip(class_uses_recursive(static::class));
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['sirsoft-board' => require base_path('modules/_bundled/sirsoft-board/config/board.php')]);
        $this->app->register(BoardServiceProvider::class);
        $this->app->bind(TravelSupportPostRepositoryInterface::class, TravelSupportPostRepository::class);
        app('translator')->addNamespace('raonslab-travel_lab', dirname(__DIR__).'/src/lang');

        HookListenerRegistrar::register(SuppressTravelSupportNotifications::class, 'raonslab-travel_lab');
        HookListenerRegistrar::register(ExcludeTravelSupportQuestionsFromSearch::class, 'raonslab-travel_lab');

        $this->registerSupportRoutes();
        $this->createDefaultRoles();
    }

    /**
     * LAB 허용 설정과 확인 플래그로 고객지원 게시판을 준비합니다.
     *
     * @return array<string, mixed>
     */
    protected function provisionSupport(): array
    {
        config(['raonslab-travel_lab.support.lab_provisioning' => true]);

        return app(TravelSupportProvisioner::class)->provision(labConfirmed: true);
    }

    /**
     * 고객지원 관리자 권한을 가진 사용자를 만듭니다 (사용자 전용 역할에 권한 부여).
     *
     * @param  array<int, string>  $permissions
     */
    protected function createSupportAdmin(array $permissions): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('identifier', 'admin')->firstOrFail());

        $ownRole = Role::create([
            'identifier' => 'test-support-admin-'.$user->id,
            'name' => ['ko' => 'test', 'en' => 'test'],
        ]);
        $user->roles()->attach($ownRole);

        foreach (['admin.access', ...$permissions] as $identifier) {
            $permission = Permission::firstOrCreate(
                ['identifier' => $identifier],
                ['name' => ['ko' => $identifier, 'en' => $identifier], 'type' => 'admin'],
            );
            $ownRole->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return $user->fresh();
    }

    protected function createMember(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('identifier', 'user')->firstOrFail());

        return $user;
    }

    /**
     * 통합 후 ModuleRouteServiceProvider 와 동일한 prefix/name/middleware 로 라우트를 등록합니다.
     */
    private function registerSupportRoutes(): void
    {
        Route::prefix('api/modules/raonslab-travel_lab')
            ->name('api.modules.raonslab-travel_lab.')
            ->middleware('api')
            ->group(dirname(__DIR__).'/src/routes/support.php');

        Route::prefix('api/modules/sirsoft-board')
            ->name('api.modules.sirsoft-board.')
            ->middleware('api')
            ->group(base_path('modules/_bundled/sirsoft-board/src/routes/api.php'));

        Route::getRoutes()->refreshNameLookups();
    }

    private function createDefaultRoles(): void
    {
        foreach (['admin', 'user', 'guest'] as $identifier) {
            Role::firstOrCreate(
                ['identifier' => $identifier],
                ['name' => ['ko' => $identifier, 'en' => $identifier]],
            );
        }
    }
}
