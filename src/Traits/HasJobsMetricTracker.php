<?php

namespace Omaralalwi\JobsMetrics\Traits;

use Omaralalwi\JobsMetrics\Middleware\JobsMetricTracker;

trait HasJobsMetricTracker
{
    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        $middleware = [new JobsMetricTracker];
        
        // If parent has middleware method, merge with it
        if (method_exists(get_parent_class($this), 'middleware')) {
            $parentMiddleware = parent::middleware();
            if (is_array($parentMiddleware)) {
                return array_merge($middleware, $parentMiddleware);
            }
        }
        
        return $middleware;
    }
}
