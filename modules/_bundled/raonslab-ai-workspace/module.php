<?php

namespace Modules\Raonslab\Ai\Workspace;

use App\Extension\AbstractModule;

/**
 * G7 사용자와 AI_GCS V2 Request API 사이의 명시적 통합 경계입니다.
 */
class Module extends AbstractModule
{
    /** @return array<string, string> */
    public function getConfig(): array
    {
        return [
            'raonslab-ai-workspace' => $this->getModulePath().'/config/ai.php',
        ];
    }

    /**
     * 모든 로그인 회원이 자신의 요청만 사용할 수 있는 사용자 권한입니다.
     *
     * @return array<string, mixed>
     */
    public function getPermissions(): array
    {
        return [
            'name' => ['ko' => 'AI 작업공간', 'en' => 'AI Workspace'],
            'description' => ['ko' => 'AI 작업 요청과 결과 조회', 'en' => 'AI requests and results'],
            'categories' => [[
                'identifier' => 'requests',
                'resource_route_key' => 'request_id',
                'owner_key' => 'user_id',
                'name' => ['ko' => 'AI 요청', 'en' => 'AI Requests'],
                'description' => ['ko' => '본인의 AI 요청 사용', 'en' => 'Use owned AI requests'],
                'permissions' => [[
                    'action' => 'use',
                    'name' => ['ko' => 'AI 요청 사용', 'en' => 'Use AI requests'],
                    'description' => ['ko' => 'AI 요청 제출, 조회 및 후속 지시', 'en' => 'Submit, view, and follow up AI requests'],
                    'type' => 'user',
                    'roles' => ['user', 'manager', 'admin'],
                ]],
            ]],
        ];
    }
}
