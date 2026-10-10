<?php

// 운영 환경에서는 기본 차단. 격리 LAB 표식과 명시 설정을 모두 요구합니다.
return [
    'lab_provisioning' => filter_var(env('TRAVEL_LAB_ISOLATED', false), FILTER_VALIDATE_BOOLEAN)
        && filter_var(env('TRAVEL_LAB_SUPPORT_PROVISIONING', false), FILTER_VALIDATE_BOOLEAN),
];
