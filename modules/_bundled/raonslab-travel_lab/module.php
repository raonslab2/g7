<?php

namespace Modules\Raonslab\TravelLab;

use App\Extension\AbstractModule;
use Modules\Raonslab\TravelLab\Database\Seeders\DatabaseSeeder;
use Modules\Raonslab\TravelLab\Listeners\BlockTravelCommerceCheckout;
use Modules\Raonslab\TravelLab\Listeners\ExcludeTravelSupportQuestionsFromSearch;
use Modules\Raonslab\TravelLab\Listeners\InvalidateTravelCampaignSeoCache;
use Modules\Raonslab\TravelLab\Listeners\ProtectTravelCommerceCatalog;
use Modules\Raonslab\TravelLab\Listeners\SuppressTravelSupportNotifications;

class Module extends AbstractModule
{
    /** 테스트 데이터는 설치 시 자동 생성하지 않습니다. */
    public function getSeeders(): array
    {
        return [DatabaseSeeder::class];
    }

    public function getHookListeners(): array
    {
        return [
            BlockTravelCommerceCheckout::class,
            ProtectTravelCommerceCatalog::class,
            SuppressTravelSupportNotifications::class,
            ExcludeTravelSupportQuestionsFromSearch::class,
            InvalidateTravelCampaignSeoCache::class,
        ];
    }

    public function getAdminMenus(): array
    {
        $menus = array_map(fn ($key, $label) => [
            'name' => ['ko' => $label[0], 'en' => $label[1]],
            'slug' => 'travel-lab-'.$key, 'url' => '/admin/travel-lab/'.$key,
            'icon' => 'fas fa-plane', 'order' => 90,
            'permission' => 'raonslab-travel_lab.'.$key.'.read',
        ], ['catalog', 'inquiries', 'support'], [
            ['여행 카탈로그', 'Travel catalog'], ['시험 접수', 'Test inquiries'], ['여행 고객지원', 'Travel support'],
        ]);

        // 캠페인 문서는 native sirsoft-page 가 소유한다 — 메뉴 노출도 native Page 읽기 권한을 따른다.
        $menus[] = [
            'name' => ['ko' => '여행 캠페인', 'en' => 'Travel campaigns'],
            'slug' => 'travel-lab-campaigns', 'url' => '/admin/travel-lab/campaigns',
            'icon' => 'fas fa-plane', 'order' => 90,
            'permission' => 'sirsoft-page.pages.read',
        ];

        return $menus;
    }

    public function getPermissions(): array
    {
        $categories = [];
        foreach (['catalog' => ['여행 카탈로그', 'Travel catalog'], 'inquiries' => ['테스트 문의', 'Test inquiries'], 'support' => ['여행 지원', 'Travel support']] as $identifier => $labels) {
            $permissions = [];
            foreach (['read' => ['조회', 'Read'], 'update' => ['수정', 'Update']] as $action => $verbs) {
                $permissions[] = [
                    'action' => $action,
                    'name' => ['ko' => $labels[0].' '.$verbs[0], 'en' => $verbs[1].' '.$labels[1]],
                    'description' => ['ko' => $labels[0].' '.$verbs[0].' 권한', 'en' => $verbs[1].' '.$labels[1]],
                    'type' => 'admin',
                    'roles' => $identifier === 'support' ? ['admin'] : ['admin', 'manager'],
                ];
            }
            $categories[] = ['identifier' => $identifier, 'owner_key' => $identifier === 'inquiries' ? 'user_id' : null, 'resource_route_key' => $identifier === 'inquiries' ? 'inquiry' : null, 'name' => ['ko' => $labels[0], 'en' => $labels[1]], 'description' => ['ko' => $labels[0].' 관리 권한', 'en' => $labels[1].' permissions'], 'permissions' => $permissions];
        }

        return ['name' => ['ko' => '트래블 랩', 'en' => 'Travel Lab'], 'description' => ['ko' => '여행 연구소 관리 권한', 'en' => 'Travel Lab permissions'], 'categories' => $categories];
    }
}
