<?php

return [
    'messages' => [
        'list_loaded' => '캠페인 목록을 불러왔습니다.',
        'loaded' => '캠페인을 불러왔습니다.',
    ],
    'errors' => [
        'not_found' => '캠페인을 찾을 수 없습니다.',
        'slug_invalid' => '캠페인 주소 형식이 올바르지 않습니다.',
        'selector_prohibited' => '캠페인은 정해진 두 슬롯만 제공되며 조회 대상을 지정할 수 없습니다.',
        'provisioning_not_allowed' => '캠페인 LAB 프로비저닝이 허용되지 않았습니다. TRAVEL_LAB_ISOLATED·TRAVEL_LAB_CAMPAIGN_PROVISIONING 설정과 --lab-confirm 을 확인하세요.',
        'unsafe_environment' => ':setting 설정이 :actual 입니다. 캠페인 프로비저닝은 :expected 구성에서만 허용됩니다.',
        'actor_invalid' => '--actor 에 존재하는 관리자 사용자 ID 를 지정하세요.',
        'actor_not_permitted' => '사용자 :actor 에게 페이지 읽기·생성 관리자 권한이 없어 아무것도 만들지 않았습니다.',
        'slug_conflict' => ':slug 페이지가 생성 도중 이미 만들어져 덮어쓰지 않고 중단했습니다.',
    ],
];
