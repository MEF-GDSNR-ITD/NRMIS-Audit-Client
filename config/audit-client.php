<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the centralized audit logging service client
    |
    */

    // Base URL of the audit service
    'base_url' => env('AUDIT_SERVICE_URL', 'http://audit-service:8080/api/v1'),

    // Service identification
    'service_name' => env('AUDIT_SERVICE_NAME', config('app.name', 'unknown-service')),
    'service_version' => env('AUDIT_SERVICE_VERSION', '1.0.0'),
    'environment' => env('AUDIT_ENVIRONMENT', config('app.env', 'production')),

    // HTTP Client settings
    'timeout' => env('AUDIT_TIMEOUT', 10),
    'connect_timeout' => env('AUDIT_CONNECT_TIMEOUT', 5),

    // Async settings
    'async' => env('AUDIT_ASYNC', true),

    // Default metadata to include with all audit logs
    'default_metadata' => [
        // Add any default metadata here
    ],

    // Enable/disable audit logging
    'enabled' => env('AUDIT_ENABLED', true),

    // Retry settings
    'retry_attempts' => env('AUDIT_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('AUDIT_RETRY_DELAY', 1000), // milliseconds

    // Queue settings (for async processing)
    'queue' => [
        'connection' => env('AUDIT_QUEUE_CONNECTION', 'redis'),
        'queue' => env('AUDIT_QUEUE_NAME', 'audit-logs'),
    ],

    // Buffer settings (for batch processing)
    'buffer' => [
        'enabled' => env('AUDIT_BUFFER_ENABLED', false),
        'size' => env('AUDIT_BUFFER_SIZE', 100),
        'flush_interval' => env('AUDIT_BUFFER_FLUSH_INTERVAL', 60), // seconds
    ],

    // Security settings
    'api_key' => env('AUDIT_API_KEY'),
    'verify_ssl' => env('AUDIT_VERIFY_SSL', true),

    // Model auditing settings
    'model_auditing' => [
        'enabled' => env('AUDIT_MODEL_AUDITING', true),
        'exclude_attributes' => [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
        ],
    ],
];
