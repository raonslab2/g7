<?php

namespace Modules\Raonslab\Product;

use App\Extension\AbstractModule;
use Modules\Raonslab\Product\Http\Middleware\EnsureConsultationIntakeEnabled;
use Modules\Raonslab\Product\Http\Middleware\RequireSameOrigin;
use Modules\Raonslab\Product\Listeners\ApplyHomeSeoMeta;
use Modules\Raonslab\Product\Listeners\ExcludeConsultationPostsFromSearch;
use Modules\Raonslab\Product\Listeners\SuppressQaContentNotifications;
use Modules\Raonslab\Product\Services\ConsultationBoardProvisioner;
use Throwable;

/**
 * RAON 제품 서비스 모듈
 *
 * 공개 화면 확장과 G7 확장 지점을 통한 사업 상담 backend/admin 기능을 제공합니다.
 */
class Module extends AbstractModule
{
    public function getHookListeners(): array
    {
        return [
            ApplyHomeSeoMeta::class,
            ExcludeConsultationPostsFromSearch::class,
            SuppressQaContentNotifications::class,
        ];
    }

    public function getConfig(): array
    {
        return [
            'raonslab-product-consultations' => $this->getModulePath().'/config/consultations.php',
        ];
    }

    /**
     * 관리자 "사업 상담" 메뉴는 비공개 상담 게시판의 관리자 화면을 가리킵니다.
     *
     * 0.3.0에서 선언을 비우자 코어의 데이터 손실 방어로 기존 메뉴 행이 정리되지 않고
     * 폐기된 /admin/consultations(410 API)를 계속 가리켰습니다. 같은 slug를 다시 선언해
     * 기존 행의 URL을 동기화합니다.
     */
    public function getAdminMenus(): array
    {
        return [
            [
                'name' => ['ko' => '사업 상담', 'en' => 'Consultations'],
                'slug' => 'raonslab-product-consultations',
                'url' => '/admin/board/'.rawurlencode((string) config('raonslab-product-consultations.board_slug', 'raon-consultations')),
                'icon' => 'fas fa-comments',
                'order' => 80,
            ],
        ];
    }

    public function install(): bool
    {
        return $this->provisionConsultationBoard();
    }

    public function activate(): bool
    {
        return $this->provisionConsultationBoard();
    }

    public function getMiddleware(): array
    {
        return [
            [
                'class' => RequireSameOrigin::class,
                'groups' => ['api'],
                'timing' => 'before_core',
                'targets' => ['api.modules.raonslab-product.consultations.store'],
            ],
            [
                'class' => EnsureConsultationIntakeEnabled::class,
                'groups' => ['api'],
                'targets' => ['api.modules.raonslab-product.consultations.store'],
            ],
        ];
    }

    private function provisionConsultationBoard(): bool
    {
        try {
            app(ConsultationBoardProvisioner::class)->ensureReady();

            return true;
        } catch (Throwable) {
            return $this->failWith(__('common.failed'));
        }
    }
}
