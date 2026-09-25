<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automation API
    |--------------------------------------------------------------------------
    |
    | Secret values are loaded from the environment. Do not place credentials
    | directly in this file or commit them to the repository.
    |
    */

    'enabled' => (bool) env('AUTOMATION_ENABLED', false),

    'api' => [
        'base_url' => env('AUTOMATION_API_URL'),
        'timeout_seconds' => (int) env('AUTOMATION_API_TIMEOUT_SECONDS', 30),
        'connect_timeout_seconds' => (int) env('AUTOMATION_API_CONNECT_TIMEOUT_SECONDS', 5),
        'retry_times' => (int) env('AUTOMATION_API_RETRY_TIMES', 3),
        'retry_sleep_milliseconds' => (int) env('AUTOMATION_API_RETRY_SLEEP_MILLISECONDS', 250),
    ],

    'client' => [
        'id' => env('AUTOMATION_CLIENT_ID', 'magna_gestao'),
        'secret' => env('AUTOMATION_CLIENT_SECRET'),
        'previous_secret' => env('AUTOMATION_CLIENT_PREVIOUS_SECRET'),
    ],

    'webhook' => [
        'client_id' => env('AUTOMATION_WEBHOOK_CLIENT_ID', env('AUTOMATION_WEBHOOK_ISSUER_ID', 'automation_prod')),
        'secret' => env('AUTOMATION_WEBHOOK_SECRET'),
        'previous_secret' => env('AUTOMATION_WEBHOOK_PREVIOUS_SECRET'),
    ],

    'signature' => [
        'version' => 'v1',
        'encoding' => 'hex',
        'timestamp_tolerance_seconds' => (int) env('AUTOMATION_HMAC_TIMESTAMP_TOLERANCE_SECONDS', env('AUTOMATION_SIGNATURE_TIMESTAMP_TOLERANCE_SECONDS', 300)),
        'nonce_ttl_seconds' => (int) env('AUTOMATION_SIGNATURE_NONCE_TTL_SECONDS', 600),
        'nonce_cache_store' => env('AUTOMATION_SIGNATURE_NONCE_CACHE_STORE', 'redis'),
    ],

    'routes' => [
        'webhook' => '/api/integrations/automation/v1/webhooks',
    ],

    'queues' => [
        'submission' => env('AUTOMATION_SUBMISSION_QUEUE', 'automation'),
        'processing' => env('AUTOMATION_PROCESSING_QUEUE', 'automation-import'),
    ],

    'default_report' => 'daily_trip_summary',

    'reports' => [
        'daily_trip_summary' => [
            'collector' => 'daily_trip_summary',
            'collector_version' => '1.0.0',
            'schema_version' => '1.0',
            'default_unidade_negocio' => env('AUTOMATION_DAILY_TRIP_SUMMARY_DEFAULT_UNIDADE_NEGOCIO'),
            'result_page_limit' => (int) env('AUTOMATION_DAILY_TRIP_SUMMARY_RESULT_PAGE_LIMIT', 500),
            'parameter_rules' => [
                'from' => ['required', 'date_format:Y-m-d'],
                'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            ],
            'fields' => [
                'numero_viagem',
                'placa',
                'cliente',
                'destino',
                'km_rodado',
                'km_pago',
                'data_competencia',
                'data_inicio',
                'data_fim',
                'possui_pendencia',
                'pendencias',
                'motoristas',
            ],
            'motoristas_format' => 'lotes',
        ],
    ],

    'schedules' => [
        'daily_trip_summary' => [
            'enabled' => (bool) env('AUTOMATION_DAILY_TRIP_SUMMARY_SCHEDULE_ENABLED', false),
            'cron' => env('AUTOMATION_DAILY_TRIP_SUMMARY_SCHEDULE_CRON', '0 2 * * *'),
            'timezone' => env('AUTOMATION_DAILY_TRIP_SUMMARY_SCHEDULE_TIMEZONE', 'America/Sao_Paulo'),
            'days_offset' => (int) env('AUTOMATION_DAILY_TRIP_SUMMARY_DAYS_OFFSET', 1),
        ],
    ],

];
