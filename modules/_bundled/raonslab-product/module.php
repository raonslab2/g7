<?php

namespace Modules\Raonslab\Product;

use App\Extension\AbstractModule;
use Modules\Raonslab\Product\Http\Middleware\EnsureConsultationIntakeEnabled;
use Modules\Raonslab\Product\Http\Middleware\RequireSameOrigin;
use Modules\Raonslab\Product\Listeners\ApplyHomeSeoMeta;

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

    public function getPermissions(): array
    {
        return [
            'name' => ['ko' => 'RAON 사업 상담', 'en' => 'RAON Consultations'],
            'description' => ['ko' => '사업 상담 접수 관리', 'en' => 'Manage business consultations'],
            'categories' => [[
                'identifier' => 'consultations',
                'resource_route_key' => 'consultation',
                'owner_key' => null,
                'name' => ['ko' => '사업 상담', 'en' => 'Consultations'],
                'description' => ['ko' => '사업 상담 조회 및 처리', 'en' => 'View and process consultations'],
                'permissions' => [
                    [
                        'action' => 'read',
                        'name' => ['ko' => '상담 조회', 'en' => 'View consultations'],
                        'description' => ['ko' => '상담 목록과 상세 조회', 'en' => 'View consultation list and details'],
                        'type' => 'admin',
                        'roles' => ['admin'],
                    ],
                    [
                        'action' => 'manage',
                        'name' => ['ko' => '상담 처리', 'en' => 'Manage consultations'],
                        'description' => ['ko' => '내부 메모와 상태 변경', 'en' => 'Add notes and change status'],
                        'type' => 'admin',
                        'roles' => ['admin'],
                    ],
                ],
            ]],
        ];
    }

    public function getAdminMenus(): array
    {
        return [[
            'name' => ['ko' => '사업 상담', 'en' => 'Consultations'],
            'slug' => 'raonslab-product-consultations',
            'url' => '/admin/consultations',
            'icon' => 'fas fa-comments',
            'order' => 80,
            'permission' => 'raonslab-product.consultations.read',
        ]];
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
}
