<?php

use Modules\Raonslab\TravelLab\Enums\Region;
use Modules\Raonslab\TravelLab\Enums\Theme;

return [
    // RAON 실증 자체 결정: 국내 출발 일정은 한국 영업일 기준입니다.
    'business_timezone' => 'Asia/Seoul',
    // 운영자가 관리하는 작은 분류 목록입니다. 상품 도메인을 별도로 만들지 않습니다.
    'regions' => array_column(Region::cases(), 'value'),
    'themes' => array_column(Theme::cases(), 'value'),
    'sample_departure_anchor' => '2026-11-01',
];
