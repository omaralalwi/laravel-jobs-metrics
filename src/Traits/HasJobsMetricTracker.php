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

        $parent = get_parent_class($this);

        if ($parent && method_exists($parent, 'middleware')) {
            $parentMiddleware = parent::middleware();
            if (is_array($parentMiddleware)) {
                return array_merge($middleware, $parentMiddleware);
            }
        }

        return $middleware;
    }

}
