<?php

namespace Omaralalwi\JobsMetrics\Tests;

use Omaralalwi\JobsMetrics\JobsMetricsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            JobsMetricsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        
        // Explicitly enable job metrics tracking for tests
        $app['config']->set('jobs-metrics.track_jobs_metrics', true);
        $app['config']->set('jobs-metrics.log_errors', true);
    }
    
    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
} 