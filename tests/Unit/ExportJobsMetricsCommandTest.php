<?php

namespace Omaralalwi\JobsMetrics\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Omaralalwi\JobsMetrics\Models\JobsMetric;
use Omaralalwi\JobsMetrics\Services\JobsMetricsExporter;
use Omaralalwi\JobsMetrics\Services\JobsMetricsFormatter;
use Omaralalwi\JobsMetrics\Services\JobsMetricsQueryBuilder;
use Omaralalwi\JobsMetrics\Tests\TestCase;
use Mockery;

class ExportJobsMetricsCommandTest extends TestCase
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
    public function it_can_export_job_metrics_to_json()
    {
        // Create test data
        JobsMetric::create([
            'job' => 'ExportJob',
            'queue' => 'default',
            'duration_ms' => 100,
            'memory_mb' => 20,
            'created_at' => Carbon::now()->subHours(1)
        ]);
        
        // Run the export command
        $this->artisan('jobs-metrics:export')
            ->expectsOutputToContain('Exporting comprehensive job metrics data')
            ->expectsOutputToContain('Metrics data exported to')
            ->assertSuccessful();
            
        // Check that the export path exists
        $exportPath = storage_path('app/jobs-metrics-export');
        $this->assertTrue(File::exists($exportPath));
        
        // Check that at least one JSON file exists in the directory
        $jsonFiles = File::glob("{$exportPath}/*.json");
        $this->assertNotEmpty($jsonFiles);
        
        // Check the content of the exported file
        $latestFile = end($jsonFiles);
        $content = json_decode(File::get($latestFile), true);
        
        $this->assertArrayHasKey('job_metrics', $content);
        $this->assertArrayHasKey('queue_metrics', $content);
        $this->assertArrayHasKey('export_time', $content);
        $this->assertArrayHasKey('period', $content);
    }
    
    /** @test */
    public function it_respects_days_option()
    {
        // Create jobs with different dates
        JobsMetric::create([
            'job' => 'RecentJob',
            'queue' => 'default',
            'duration_ms' => 100,
            'memory_mb' => 20,
            'created_at' => Carbon::now()->subDays(1)
        ]);
        
        JobsMetric::create([
            'job' => 'OlderJob',
            'queue' => 'default',
            'duration_ms' => 150,
            'memory_mb' => 25,
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        // Mock formatter to avoid null issues
        $mockFormatter = Mockery::mock(JobsMetricsFormatter::class);
        $mockFormatter->shouldReceive('setOptions');
        $mockFormatter->shouldReceive('formatNumber')->andReturnUsing(function($value) {
            return is_numeric($value) ? round((float)$value, 2) : 0;
        });
        
        $this->app->instance(JobsMetricsFormatter::class, $mockFormatter);
        
        // Run the command with days=5 option
        $this->artisan('jobs-metrics:export --days=5')
            ->assertSuccessful();
    }
    
    /** @test */
    public function it_uses_service_classes()
    {
        // Mock the exporter service
        $mockExporter = Mockery::mock(JobsMetricsExporter::class);
        $mockExporter->shouldReceive('exportJson')
            ->once()
            ->with('metrics_export', Mockery::type('array'))
            ->andReturn('/path/to/export.json');
        
        // Create test data objects for Eloquent collection 
        $jobData = new JobsMetric([
            'job' => 'TestJob',
            'queue' => 'default',
            'duration_ms' => 100.75,
            'memory_mb' => 20.5,
            'created_at' => Carbon::now(),
        ]);
        $jobData->executions = 5;
        $jobData->avg_memory = 20.5;
        $jobData->max_memory = 30.0;
        $jobData->min_memory = 10.0;
        $jobData->avg_duration = 100.75;
        $jobData->max_duration = 200.0;
        $jobData->min_duration = 50.0;
        $jobData->first_executed = Carbon::now()->subDays(5);
        $jobData->last_executed = Carbon::now();
        
        $queueData = new JobsMetric([
            'queue' => 'default',
            'duration_ms' => 100.75,
            'memory_mb' => 20.5,
            'created_at' => Carbon::now(),
        ]);
        $queueData->distinct_jobs = 1;
        $queueData->executions = 5;
        $queueData->avg_memory = 20.5;
        $queueData->max_memory = 30.0;
        $queueData->avg_duration = 100.75;
        $queueData->max_duration = 200.0;
        $queueData->first_executed = Carbon::now()->subDays(5);
        $queueData->last_executed = Carbon::now();
        
        // Mock the query builder service with Eloquent Collections
        $mockQueryBuilder = Mockery::mock(JobsMetricsQueryBuilder::class);
        $mockQueryBuilder->shouldReceive('setOptions')->once();
        $mockQueryBuilder->shouldReceive('getAggregatedJobMetrics')
            ->once()
            ->andReturn(new EloquentCollection([$jobData]));
            
        $mockQueryBuilder->shouldReceive('getQueueMetrics')
            ->once()
            ->andReturn(new EloquentCollection([$queueData]));
        
        // Mock the formatter service
        $mockFormatter = Mockery::mock(JobsMetricsFormatter::class);
        $mockFormatter->shouldReceive('setOptions')->once();
        $mockFormatter->shouldReceive('formatNumber')->andReturnUsing(function($value) {
            return is_numeric($value) ? round((float)$value, 2) : 0;
        });
        
        // Replace the services in the container
        $this->app->instance(JobsMetricsExporter::class, $mockExporter);
        $this->app->instance(JobsMetricsQueryBuilder::class, $mockQueryBuilder);
        $this->app->instance(JobsMetricsFormatter::class, $mockFormatter);
        
        // Run the command
        $this->artisan('jobs-metrics:export')
            ->expectsOutputToContain('Metrics data exported to: /path/to/export.json')
            ->assertSuccessful();
    }
} 