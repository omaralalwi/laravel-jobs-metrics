<?php

namespace Omaralalwi\JobsMetrics;

use Illuminate\Support\ServiceProvider;
use Omaralalwi\JobsMetrics\Console\Commands\CleanupJobsMetricsCommand;
use Omaralalwi\JobsMetrics\Console\Commands\ExportJobsMetricsCommand;
use Omaralalwi\JobsMetrics\Console\Commands\ShowTopJobsCommand;
use Omaralalwi\JobsMetrics\Services\JobsMetricsExporter;
use Omaralalwi\JobsMetrics\Services\JobsMetricsFormatter;
use Omaralalwi\JobsMetrics\Services\JobsMetricsQueryBuilder;

class JobsMetricsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/jobs-metrics.php' => config_path('jobs-metrics.php'),
            ], 'config');
            
            $this->commands([
                CleanupJobsMetricsCommand::class,
                ShowTopJobsCommand::class,
                ExportJobsMetricsCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/jobs-metrics.php', 'jobs-metrics');
        
        // Register service classes
        $this->app->singleton(JobsMetricsQueryBuilder::class);
        $this->app->singleton(JobsMetricsFormatter::class);
        $this->app->singleton(JobsMetricsExporter::class);
    }
} 