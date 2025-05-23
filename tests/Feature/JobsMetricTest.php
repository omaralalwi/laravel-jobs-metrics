<?php

namespace Omaralalwi\JobsMetrics\Tests\Feature;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Omaralalwi\JobsMetrics\Middleware\JobsMetricTracker;
use Omaralalwi\JobsMetrics\Models\JobsMetric;
use Omaralalwi\JobsMetrics\Tests\TestCase;
use Omaralalwi\JobsMetrics\Traits\HasJobsMetricTracker;

class JobsMetricTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing metrics
        JobsMetric::query()->delete();
        
        // Ensure metrics tracking is enabled
        Config::set('jobs-metrics.track_jobs_metrics', true);
    }

    /** @test */
    public function it_records_job_metrics_when_a_job_is_processed()
    {
        // Create a test job
        $job = new TestJob();
        
        // Manually apply middleware to simulate queue processing
        $middleware = new JobsMetricTracker();
        $middleware->handle($job, function ($job) {
            $job->handle();
            return true;
        });
        
        // Assert that metrics were recorded
        $this->assertDatabaseHas('jobs_metrics', [
            'job' => TestJob::class,
        ]);
        
        // Get the recorded metric
        $metric = JobsMetric::where('job', TestJob::class)->first();
        
        // Verify necessary fields
        $this->assertNotNull($metric);
        $this->assertEquals('default', $metric->queue);
        $this->assertGreaterThan(0, $metric->duration_ms);
        $this->assertGreaterThan(0, $metric->memory_mb);
    }
    
    /** @test */
    public function it_does_not_record_metrics_when_disabled()
    {
        // Disable metrics tracking
        Config::set('jobs-metrics.track_jobs_metrics', false);
        
        // Create a test job
        $job = new TestJob();
        
        // Manually apply middleware to simulate queue processing
        $middleware = new JobsMetricTracker();
        $middleware->handle($job, function ($job) {
            $job->handle();
            return true;
        });
        
        // Assert that no metrics were recorded
        $this->assertDatabaseMissing('jobs_metrics', [
            'job' => TestJob::class,
        ]);
        
        // Assert that we have no metrics
        $this->assertEquals(0, JobsMetric::count());
    }
    
    /** @test */
    public function it_records_queue_name_properly()
    {
        // Create a test job with a custom queue
        $job = new TestJobWithCustomQueue();
        
        // Manually apply middleware to simulate queue processing
        $middleware = new JobsMetricTracker();
        $middleware->handle($job, function ($job) {
            $job->handle();
            return true;
        });
        
        // Assert queue is recorded correctly
        $this->assertDatabaseHas('jobs_metrics', [
            'job' => TestJobWithCustomQueue::class,
            'queue' => 'high-priority',
        ]);
    }
}

class TestJob implements ShouldQueue
{
    use InteractsWithQueue, HasJobsMetricTracker;
    
    public $queue = 'default';
    
    public function handle()
    {
        // Simulate some work
        usleep(100000); // 100ms
        $array = [];
        for ($i = 0; $i < 1000; $i++) {
            $array[] = str_repeat('a', 1000);
        }
    }
}

class TestJobWithCustomQueue implements ShouldQueue
{
    use InteractsWithQueue, HasJobsMetricTracker;
    
    public $queue = 'high-priority';
    
    public function handle()
    {
        // Simulate some work
        usleep(50000); // 50ms
        $array = [];
        for ($i = 0; $i < 500; $i++) {
            $array[] = str_repeat('b', 500);
        }
    }
} 