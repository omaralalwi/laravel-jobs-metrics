<?php

namespace Omaralalwi\JobsMetrics\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Omaralalwi\JobsMetrics\Models\JobsMetric;
use Omaralalwi\JobsMetrics\Tests\TestCase;

class CleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_cleans_up_old_records()
    {
        $now = Carbon::now();
        $oldDate = $now->copy()->subDays(60)->toDateTimeString(); // 60 days old - should be deleted
        $newDate = $now->copy()->subDays(10)->toDateTimeString(); // 10 days old - should be kept
        
        DB::table('jobs_metrics')->insert([
            'job' => 'OldJob',
            'queue' => 'default',
            'duration_ms' => 100,
            'memory_mb' => 10,
            'created_at' => $oldDate,
            'updated_at' => $oldDate
        ]);
        
        DB::table('jobs_metrics')->insert([
            'job' => 'NewJob',
            'queue' => 'default',
            'duration_ms' => 100,
            'memory_mb' => 10,
            'created_at' => $newDate,
            'updated_at' => $newDate
        ]);
        
        $this->assertEquals(2, DB::table('jobs_metrics')->count());
        
        $this->artisan('jobs-metrics:cleanup --days=30')
            ->execute();
        
        $count = DB::table('jobs_metrics')->count();
        $this->assertEquals(1, $count, "Expected 1 record after cleanup, but found {$count}");
        
        $remaining = DB::table('jobs_metrics')->where('job', 'NewJob')->count();
        $this->assertEquals(1, $remaining, "Expected NewJob to remain");
        
        $deleted = DB::table('jobs_metrics')->where('job', 'OldJob')->count();
        $this->assertEquals(0, $deleted, "Expected OldJob to be deleted");
    }
} 