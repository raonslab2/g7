<?php

return [
    /*
    | 접수는 운영자가 아래 값을 모두 명시한 경우에만 열립니다.
    | 기본값은 의도적으로 비어 있으며, 미확정 운영값을 코드가 발명하지 않습니다.
    */
    'enabled' => filter_var(env('RAON_CONSULTATION_INTAKE_ENABLED', false), FILTER_VALIDATE_BOOL),
    'consent_version' => trim((string) env('RAON_CONSULTATION_CONSENT_VERSION', '')),
    'privacy_copy' => trim((string) env('RAON_CONSULTATION_PRIVACY_COPY', '')),
    'privacy_policy_url' => trim((string) env('RAON_CONSULTATION_PRIVACY_POLICY_URL', '')),
    'privacy_contact' => trim((string) env('RAON_CONSULTATION_PRIVACY_CONTACT', '')),
    'retention_notice' => trim((string) env('RAON_CONSULTATION_RETENTION_NOTICE', '')),
    'board_slug' => trim((string) env('RAON_CONSULTATION_BOARD_SLUG', 'raon-consultations')),
];
