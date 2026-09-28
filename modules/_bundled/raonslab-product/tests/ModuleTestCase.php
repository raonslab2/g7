<?php

namespace Modules\Raonslab\Product\Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\Product\Http\Middleware\EnsureConsultationIntakeEnabled;
use Modules\Raonslab\Product\Http\Middleware\RequireSameOrigin;
use Modules\Raonslab\Product\Providers\ProductServiceProvider;
use Tests\TestCase;

/**
 * 제품화 모듈 테스트의 공통 애플리케이션 부트스트랩입니다.
 */
abstract class ModuleTestCase extends TestCase
{
    use RefreshDatabase;

    /** 접수는 https 요청에서만 열리므로 합성 요청은 https 로 보냅니다. */
    protected const SECURE_ORIGIN = 'https://localhost';

    protected const STORE_PATH = '/api/modules/raonslab-product/consultations';

    /**
     * 이 모듈의 집중 테스트는 코어 + 자기 additive migration만 필요합니다.
     * 루트 TestCase의 전체 번들 마이그레이션 등록을 우회해 테스트 순환을 줄입니다.
     *
     * @return array<class-string, int>
     */
    protected function setUpTraits()
    {
        app('migrator')->path(dirname(__DIR__).'/database/migrations');
        $this->refreshDatabase();

        return array_flip(class_uses_recursive(static::class));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ProductServiceProvider::class);
        $this->registerModuleRoutes();
        $this->createDefaultRoles();
        $this->disableIntake();
    }

    protected function enableIntake(): void
    {
        config([
            'app.url' => 'https://consult.example.test',
            'raonslab-product-consultations.enabled' => true,
            'raonslab-product-consultations.consent_version' => 'synthetic-test-v1',
            'raonslab-product-consultations.privacy_copy' => 'Synthetic test privacy notice.',
            'raonslab-product-consultations.privacy_policy_url' => 'https://example.test/privacy',
            'raonslab-product-consultations.privacy_contact' => 'privacy@example.test',
            'raonslab-product-consultations.retention_notice' => 'Synthetic test retention notice.',
            'raonslab-product-consultations.notification_to' => '',
        ]);
    }

    protected function disableIntake(): void
    {
        config([
            'raonslab-product-consultations.enabled' => false,
            'raonslab-product-consultations.consent_version' => '',
            'raonslab-product-consultations.privacy_copy' => '',
            'raonslab-product-consultations.privacy_policy_url' => '',
            'raonslab-product-consultations.privacy_contact' => '',
            'raonslab-product-consultations.retention_notice' => '',
            'raonslab-product-consultations.notification_to' => '',
        ]);
    }

    /** @return array<string, mixed> */
    protected function syntheticPayload(array $overrides = []): array
    {
        return array_replace([
            'contact_name' => 'Synthetic Visitor',
            'email' => 'synthetic@example.test',
            'company' => 'Synthetic Company',
            'phone' => '010-0000-0000',
            'service_interest' => 'Synthetic Agent Factory',
            'message' => 'This is synthetic consultation data used only by automated tests.',
            'privacy_consent' => true,
            'privacy_consent_version' => 'synthetic-test-v1',
        ], $overrides);
    }

    protected function postConsultation(array $payload, string $key, string $origin = self::SECURE_ORIGIN)
    {
        return $this->withHeaders([
            'Origin' => $origin,
            'Idempotency-Key' => $key,
        ])->postJson(self::SECURE_ORIGIN.self::STORE_PATH, $payload);
    }

    protected function getSecureConfig()
    {
        return $this->getJson(self::SECURE_ORIGIN.self::STORE_PATH.'/config');
    }

    protected function createAdminUser(array $permissions): User
    {
        $role = Role::create([
            'identifier' => 'raon-test-admin-'.uniqid(),
            'name' => ['ko' => '합성 테스트 관리자', 'en' => 'Synthetic test admin'],
        ]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        foreach (['admin.access', ...$permissions] as $identifier) {
            $permission = Permission::firstOrCreate(
                ['identifier' => $identifier],
                ['name' => ['ko' => $identifier, 'en' => $identifier], 'type' => 'admin'],
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return $user;
    }

    protected function createRegularUser(): User
    {
        $role = Role::where('identifier', 'user')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function registerModuleRoutes(): void
    {
        Route::prefix('api/modules/raonslab-product')
            ->name('api.modules.raonslab-product.')
            ->middleware('api')
            ->group(dirname(__DIR__).'/src/routes/api.php');

        $storeRoute = Route::getRoutes()->match(Request::create(
            '/api/modules/raonslab-product/consultations',
            'POST',
        ));

        $storeRoute->middleware([RequireSameOrigin::class, EnsureConsultationIntakeEnabled::class]);
        $storeRoute->computedMiddleware = null;
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
