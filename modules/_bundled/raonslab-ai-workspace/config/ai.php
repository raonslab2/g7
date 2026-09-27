<?php

return [
    'base_url' => rtrim((string) env('G7_AIGCS_V2_BASE_URL', 'http://127.0.0.1:18771'), '/'),
    'proxy_token' => (string) env('G7_AIGCS_V2_PROXY_TOKEN', ''),
    'proxy_token_file' => (string) env('G7_AIGCS_V2_PROXY_TOKEN_FILE', '/etc/g7-product/ai-gcs-v2-token'),
    'project_id' => (string) env('G7_AIGCS_V2_PROJECT_ID', 'GNUBOARD7'),
    'operator_id' => (string) env('G7_AIGCS_V2_OPERATOR_ID', 'g7-adapter'),
    'origin' => (string) env('G7_AIGCS_V2_ORIGIN', ''),
    'connect_timeout' => (int) env('G7_AIGCS_V2_CONNECT_TIMEOUT', 3),
    'request_timeout' => (int) env('G7_AIGCS_V2_REQUEST_TIMEOUT', 30),
];
