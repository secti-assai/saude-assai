<?php

return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    'delete_records_older_than_days' => 365,

    'default_log_name' => env('ACTIVITY_LOGGER_DEFAULT_LOG_NAME', 'default'),

    // If env vars are empty strings, fallback to safe defaults.
    'activity_model' => env('ACTIVITY_LOGGER_ACTIVITY_MODEL') ?: \Spatie\Activitylog\Models\Activity::class,

    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME') ?: 'activity_log',

    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION') ?: null,

    'subject_returns_soft_deleted_models' => false,
];
