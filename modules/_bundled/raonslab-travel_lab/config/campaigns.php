<?php

/*
 * 여행 캠페인 고정 슬롯 레지스트리 (RAON LAB 가정, 고객 승인 자료 아님).
 *
 * - 슬롯은 정확히 두 개다. 제목·본문·발행 상태·버전은 native sirsoft-page Page 행이 소유한다.
 * - 여기에는 가격·재고·회원 정보가 없다. catalog_query 는 기존 Theme/CatalogSort enum 값만 쓴다.
 * - CampaignRegistry 는 이 파일을 config() 가 아니라 직접 읽고 형태를 검증한다 — 런타임 config
 *   덮어쓰기로 슬롯을 늘리거나 임의 slug 를 열 수 없다.
 * - lab_provisioning 은 격리 LAB 표식과 캠페인 전용 플래그를 모두 요구하며 기본 false 다.
 */
return [
    'slots' => [
        'autumn-escape' => [
            'slug' => 'travel-lab-campaign-autumn-escape',
            'theme' => 'nature',
            'sort' => 'recommended',
            'art_variant' => 'forest',
        ],
        'weekend-reset' => [
            'slug' => 'travel-lab-campaign-weekend-reset',
            'theme' => 'wellness',
            'sort' => 'recommended',
            'art_variant' => 'lake',
        ],
    ],

    'lab_provisioning' => filter_var(env('TRAVEL_LAB_ISOLATED', false), FILTER_VALIDATE_BOOLEAN)
        && filter_var(env('TRAVEL_LAB_CAMPAIGN_PROVISIONING', false), FILTER_VALIDATE_BOOLEAN),
];
