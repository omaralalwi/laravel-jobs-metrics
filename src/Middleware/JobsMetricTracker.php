<?php

namespace Omaralalwi\JobsMetrics\Middleware;

use Omaralalwi\JobsMetrics\Models\JobsMetric;
use Illuminate\Support\Facades\Log;
use Closure;
use Throwable;

class JobsMetricTracker
{
    /**
     * Handle an incoming queued job.
     *
     * @param  mixed   $job
     * @param  Closure $next
     * @return mixed
     */
    public function handle($job, Closure $next)
    {
        if (!config('jobs-metrics.track_jobs_metrics', false)) {
            return $next($job);
        }

        // Start measuring
        $startTime = microtime(true);
        
        // Process the job
        $result = $next($job);
        
        // End measuring
        $endTime = microtime(true);

        // Prepare metrics data
        $data = [
            'job'         => get_class($job),
            'queue'       => $job->queue ?? $job->onQueue ?? null,
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_mb'   => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        // Record metrics
        try {
            JobsMetric::create($data);
        } catch (Throwable $e) {
            if (config('jobs-metrics.log_errors', true)) {
                Log::error('Failed to create job metric: ' . $e->getMessage());
            }
        }

        return $result;
    }
}
