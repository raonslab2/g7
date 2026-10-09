<?php

namespace Modules\Raonslab\TravelLab;

use App\Extension\AbstractModule;
use Modules\Raonslab\TravelLab\Database\Seeders\DatabaseSeeder;

class Module extends AbstractModule
{
    /** 테스트 데이터는 설치 시 자동 생성하지 않습니다. */
    public function getSeeders(): array
    {
        return [DatabaseSeeder::class];
    }

    public function getPermissions(): array
    {
        $categories = [];
        foreach (['catalog' => ['여행 카탈로그', 'Travel catalog'], 'inquiry' => ['테스트 문의', 'Test inquiries'], 'support' => ['여행 지원', 'Travel support']] as $identifier => $labels) {
            $permissions = [];
            foreach (['read' => ['조회', 'Read'], 'update' => ['수정', 'Update']] as $action => $verbs) {
                $permissions[] = [
                    'action' => $action,
                    'name' => ['ko' => $labels[0].' '.$verbs[0], 'en' => $verbs[1].' '.$labels[1]],
                    'description' => ['ko' => $labels[0].' '.$verbs[0].' 권한', 'en' => $verbs[1].' '.$labels[1]],
                    'type' => 'admin',
                    'roles' => ['admin', 'manager'],
                ];
            }
            $categories[] = ['identifier' => $identifier, 'name' => ['ko' => $labels[0], 'en' => $labels[1]], 'permissions' => $permissions];
        }

        return ['name' => ['ko' => '트래블 랩', 'en' => 'Travel Lab'], 'categories' => $categories];
    }
}
