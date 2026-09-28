<?php

namespace Modules\Raonslab\Product;

use App\Extension\AbstractModule;
use Modules\Raonslab\Product\Http\Middleware\EnsureConsultationIntakeEnabled;
use Modules\Raonslab\Product\Http\Middleware\RequireSameOrigin;
use Modules\Raonslab\Product\Listeners\ApplyHomeSeoMeta;
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
        ];
    }

    public function getConfig(): array
    {
        return [
            'raonslab-product-consultations' => $this->getModulePath().'/config/consultations.php',
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
