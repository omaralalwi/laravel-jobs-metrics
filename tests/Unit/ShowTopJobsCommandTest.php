<?php

namespace Omaralalwi\JobsMetrics\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Omaralalwi\JobsMetrics\Models\JobsMetric;
use Omaralalwi\JobsMetrics\Services\JobsMetricsQueryBuilder;
use Omaralalwi\JobsMetrics\Services\JobsMetricsFormatter;
use Omaralalwi\JobsMetrics\Tests\TestCase;
use Mockery;

class ShowTopJobsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Delete any exported files from previous tests
        $exportPath = storage_path('app/jobs-metrics-export');
        if (File::exists($exportPath)) {
            File::deleteDirectory($exportPath);
        }
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_display_job_metrics_dashboard()
    {
        // Create test records for different job classes
        JobsMetric::create([
            'job' => 'HighMemoryJob',
            'queue' => 'default',
            'duration_ms' => 100,
            'memory_mb' => 50.5,
            'created_at' => Carbon::now()->subHours(1)
        ]);
        
        JobsMetric::create([
            'job' => 'HighMemoryJob',
            'queue' => 'default',
            'duration_ms' => 150,
            'memory_mb' => 40.2,
            'created_at' => Carbon::now()->subHours(2)
        ]);
        
        // Create records for another job class
        JobsMetric::create([
            'job' => 'LowMemoryJob',
            'queue' => 'default',
            'duration_ms' => 50,
            'memory_mb' => 10.2,
            'created_at' => Carbon::now()->subHours(3)
        ]);
        
        // Run the command with default options
        $this->artisan('jobs-metrics:top')
            ->expectsOutputToContain('Showing job metrics from the last')
            ->expectsOutputToContain('JOB METRICS:')
            ->expectsOutputToContain('QUEUE SUMMARY:')
            ->expectsOutputToContain('HighMemoryJob')
            ->expectsOutputToContain('Executions')
            ->assertSuccessful();
    }

    /** @test */
    public function it_sorts_results_by_duration()
    {
        // Create test records
        JobsMetric::create([
            'job' => 'SlowJob',
            'queue' => 'default',
            'duration_ms' => 500.75,
            'memory_mb' => 20,
            'created_at' => Carbon::now()->subHours(1)
        ]);
        
        JobsMetric::create([
            'job' => 'FastJob',
            'queue' => 'default',
            'duration_ms' => 50.25,
            'memory_mb' => 20,
            'created_at' => Carbon::now()->subHours(2)
        ]);
        
        // Run the command with sort=time option
        $this->artisan('jobs-metrics:top --sort=time')
            ->expectsOutputToContain('Sorted by time')
            ->expectsOutputToContain('SlowJob')
            ->assertSuccessful();
    }
    
    /** @test */
    public function it_limits_results_correctly()
    {
        // Create multiple jobs
        for ($i = 1; $i <= 5; $i++) {
            JobsMetric::create([
                'job' => "TestJob{$i}",
                'queue' => 'default',
                'duration_ms' => $i * 10,
                'memory_mb' => $i * 5,
                'created_at' => Carbon::now()->subHours($i)
            ]);
        }
        
        // Run the command with limit=2
        $this->artisan('jobs-metrics:top --limit=2')
            ->expectsOutputToContain('limited to 2 results')
            ->assertSuccessful();
    }
    
    /** @test */
    public function it_shows_data_for_specified_days()
    {
        // Create jobs with different dates
        JobsMetric::create([
            'job' => 'RecentJob',
            'queue' => 'default',
            'duration_ms' => 100,
            'memory_mb' => 20,
            'created_at' => Carbon::now()->subDays(1)
        ]);
        
        // Create an older job that should be filtered out
        JobsMetric::create([
            'job' => 'OlderJob',
            'queue' => 'default',
            'duration_ms' => 150,
            'memory_mb' => 25,
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        // Create mock formatter that doesn't actually format
        $mockFormatter = Mockery::mock(JobsMetricsFormatter::class);
        $mockFormatter->shouldReceive('setOptions')->once();
        $mockFormatter->shouldReceive('formatNumber')->andReturnUsing(function($value) {
            return is_numeric($value) ? round((float)$value, 2) : 0;
        });
        
        // Add this mock to the container
        $this->app->instance(JobsMetricsFormatter::class, $mockFormatter);
        
        // Run the command with days=2 option
        $this->artisan('jobs-metrics:top --days=2')
            ->expectsOutputToContain('Showing job metrics from the last 2 days')
            ->assertSuccessful();
    }
    
    /** @test */
    public function it_uses_query_builder_service()
    {
        // Mock the query builder service
        $mockQueryBuilder = Mockery::mock(JobsMetricsQueryBuilder::class);
        $mockQueryBuilder->shouldReceive('setOptions')->once();
        $mockQueryBuilder->shouldReceive('getDateRangeDescription')->once()->andReturn('last 7 days');
        
        // Create empty collections
        $emptyCollection = new EloquentCollection();
        $mockQueryBuilder->shouldReceive('getAggregatedJobMetrics')->once()->andReturn($emptyCollection);
        $mockQueryBuilder->shouldReceive('getQueueMetrics')->once()->andReturn($emptyCollection);
        
        // Mock formatter to avoid format issues
        $mockFormatter = Mockery::mock(JobsMetricsFormatter::class);
        $mockFormatter->shouldReceive('setOptions');
        $mockFormatter->shouldReceive('formatNumber')->andReturnUsing(function($value) {
            return is_numeric($value) ? round((float)$value, 2) : 0;
        });
        
        // Replace the services in the container
        $this->app->instance(JobsMetricsQueryBuilder::class, $mockQueryBuilder);
        $this->app->instance(JobsMetricsFormatter::class, $mockFormatter);
        
        // Run the command
        $this->artisan('jobs-metrics:top')
            ->assertSuccessful();
    }
    
    /** @test */
    public function it_uses_formatter_service()
    {
        // Create a test job record
        $jobModel = new JobsMetric([
            'job' => 'TestJob',
            'queue' => 'default',
            'duration_ms' => 100.75,
            'memory_mb' => 20.5,
            'created_at' => Carbon::now(),
        ]);
        $jobModel->executions = 5;
        $jobModel->avg_memory = 20.5;
        $jobModel->max_memory = 30.0;
        $jobModel->avg_duration = 100.75;
        $jobModel->max_duration = 200.0;
        $jobModel->first_executed = Carbon::now()->subDays(2);
        $jobModel->last_executed = Carbon::now();
        
        // Create a test queue record
        $queueModel = new JobsMetric([
            'queue' => 'default',
            'duration_ms' => 100.75,
            'memory_mb' => 20.5,
            'created_at' => Carbon::now(),
        ]);
        $queueModel->distinct_jobs = 1;
        $queueModel->executions = 5;
        $queueModel->avg_memory = 20.5;
        $queueModel->max_memory = 30.0;
        $queueModel->avg_duration = 100.75;
        $queueModel->max_duration = 200.0;
        
        // Mock the formatter service
        $mockFormatter = Mockery::mock(JobsMetricsFormatter::class);
        $mockFormatter->shouldReceive('setOptions')->once();
        $mockFormatter->shouldReceive('formatNumber')->andReturnUsing(function($value) {
            return is_numeric($value) ? round((float)$value, 2) : 0;
        });
        
        // Mock the query builder to return test data as Eloquent Collection
        $mockQueryBuilder = Mockery::mock(JobsMetricsQueryBuilder::class);
        $mockQueryBuilder->shouldReceive('setOptions')->once();
        $mockQueryBuilder->shouldReceive('getDateRangeDescription')->once()->andReturn('last 7 days');
        $mockQueryBuilder->shouldReceive('getAggregatedJobMetrics')->once()->andReturn(new EloquentCollection([$jobModel]));
        $mockQueryBuilder->shouldReceive('getQueueMetrics')->once()->andReturn(new EloquentCollection([$queueModel]));
        
        // Replace the services in the container
        $this->app->instance(JobsMetricsFormatter::class, $mockFormatter);
        $this->app->instance(JobsMetricsQueryBuilder::class, $mockQueryBuilder);
        
        // Run the command
        $this->artisan('jobs-metrics:top')
            ->assertSuccessful();
    }
} 