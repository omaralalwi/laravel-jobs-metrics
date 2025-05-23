<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Job Metrics Tracking
    |--------------------------------------------------------------------------
    |
    | This option controls whether job metrics tracking is enabled.
    | When enabled, each job execution will be logged with duration and memory usage.
    |
    */
    'track_jobs_metrics' => (bool) env('TRACK_JOBS_METRICS', true),

    /*
    |--------------------------------------------------------------------------
    | Log Errors
    |--------------------------------------------------------------------------
    |
    | When enabled, any errors that occur during metrics tracking will be logged.
    |
    */
    'log_errors' => (bool) env('JOBS_METRICS_LOG_ERRORS', true),
];