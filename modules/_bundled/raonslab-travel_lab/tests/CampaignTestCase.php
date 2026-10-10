<?php

namespace Modules\Raonslab\TravelLab\Tests;

use App\Enums\PermissionType;
use App\Extension\HookListenerRegistrar;
use App\Extension\HookManager;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Listeners\InvalidateTravelCampaignSeoCache;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Module;
use Modules\Sirsoft\Page\Providers\PageServiceProvider;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * 캠페인 기능용 isolated SQLite 기저.
 *
 * native sirsoft-page 의 ServiceProvider·Repository·마이그레이션·리스너(활동 로그/SEO/검색)를 그대로
 * 등록한다. PageService 를 모킹하지 않는다. MySQL FULLTEXT·외부 Scout·브라우저는 범위 밖이다.
 */
abstract class CampaignTestCase extends ModuleTestCase
{
    use UsesDatabaseThrottleCache;

    protected const BASE = '/api/modules/raonslab-travel_lab';

    protected const AUTUMN = 'travel-lab-campaign-autumn-escape';

    protected const WEEKEND = 'travel-lab-campaign-weekend-reset';

    /** @var array{hooks: array, filters: array, dispatching: array}|null */
    private ?array $hookSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->register(PageServiceProvider::class);
        foreach (['create_activity_logs_table', 'update_i18n_fields_in_activity_logs_table', 'add_indexes_to_activity_logs_table', 'create_sitemap_urls_table'] as $name) {
            foreach (glob(base_path('database/migrations/*'.$name.'.php')) as $file) {
                (require $file)->up();
            }
        }
        $paths = glob(base_path('modules/_bundled/sirsoft-page/database/migrations/*.php'));
        sort($paths);
        foreach ($paths as $file) {
            (require $file)->up();
        }

        $ref = new \ReflectionClass(HookManager::class);
        $this->hookSnapshot = [
            'hooks' => $ref->getProperty('hooks')->getValue(),
            'filters' => $ref->getProperty('filters')->getValue(),
            'dispatching' => $ref->getProperty('dispatching')->getValue(),
        ];
        // _bundled 모듈은 ModuleManager 스캔 대상이 아니므로 실제 부트와 같은 리스너를 직접 등록한다.
        HookListenerRegistrar::clear();
        if (! class_exists(Module::class)) {
            require_once base_path('modules/_bundled/sirsoft-page/module.php');
        }
        foreach ((new Module)->getHookListeners() as $listener) {
            HookListenerRegistrar::register($listener, 'sirsoft-page');
        }
        HookListenerRegistrar::register(InvalidateTravelCampaignSeoCache::class, 'raonslab-travel_lab');

        Route::prefix('api/modules/raonslab-travel_lab')->name('api.modules.raonslab-travel_lab.')->middleware('api')
            ->group(dirname(__DIR__).'/src/routes/campaigns.php');
        Route::prefix('api/modules/sirsoft-page')->name('api.modules.sirsoft-page.')->middleware('api')
            ->group(base_path('modules/_bundled/sirsoft-page/src/routes/api.php'));
        Route::getRoutes()->refreshNameLookups();
        $this->useDatabaseThrottleCache();
    }

    protected function tearDown(): void
    {
        if ($this->hookSnapshot !== null) {
            $ref = new \ReflectionClass(HookManager::class);
            foreach ($this->hookSnapshot as $property => $value) {
                $ref->getProperty($property)->setValue(null, $value);
            }
            HookListenerRegistrar::clear();
        }
        parent::tearDown();
    }

    /**
     * 실제 role/permission 행을 가진 관리자 (scope null = 전체, 'self' = 본인 소유만).
     *
     * @param  list<string>  $permissions
     */
    protected function pageAdmin(array $permissions = ['sirsoft-page.pages.read', 'sirsoft-page.pages.create', 'sirsoft-page.pages.update'], ?string $scope = null): User
    {
        $user = User::create(['name' => 'Synthetic page admin', 'email' => 'page-'.bin2hex(random_bytes(4)).'@example.test', 'password' => 'password']);
        $role = Role::create(['identifier' => 'travel-campaign-'.bin2hex(random_bytes(4)), 'name' => ['ko' => '테스트', 'en' => 'Test']]);
        $user->roles()->attach($role->id);
        foreach (['admin.access', ...$permissions] as $identifier) {
            $isPage = str_starts_with($identifier, 'sirsoft-page.pages.');
            $permission = Permission::firstOrCreate(['identifier' => $identifier], [
                'name' => ['ko' => $identifier, 'en' => $identifier], 'type' => PermissionType::Admin,
                'resource_route_key' => $isPage ? 'page' : null, 'owner_key' => $isPage ? 'created_by' : null,
            ]);
            $role->permissions()->syncWithoutDetaching([$permission->id => ['scope_type' => $isPage ? $scope : null]]);
        }

        return $user->fresh();
    }

    protected function member(): User
    {
        return User::create(['name' => 'Synthetic member', 'email' => 'member-'.bin2hex(random_bytes(4)).'@example.test', 'password' => 'password']);
    }

    protected function bearer(User $user): string
    {
        return 'Bearer '.$user->createToken('travel-campaign-tests')->plainTextToken;
    }

    /**
     * native PageService 로 Page 를 만든다 (버전 1 스냅샷·훅 포함).
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function nativeCreate(User $actor, string $slug, array $overrides = []): Page
    {
        return $this->asActor($actor, fn () => app(PageService::class)->createPage(array_replace([
            'slug' => $slug,
            'title' => ['ko' => '가을 숲 캠페인', 'en' => 'Autumn woods campaign'],
            'content' => ['ko' => "첫 줄\n둘째 줄", 'en' => "First line\nSecond line"],
            'content_mode' => 'text',
            'published' => true,
        ], $overrides)));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function asActor(User $actor, callable $callback): mixed
    {
        Auth::guard()->setUser($actor);
        try {
            return $callback();
        } finally {
            Auth::guard()->forgetUser();
        }
    }
}
