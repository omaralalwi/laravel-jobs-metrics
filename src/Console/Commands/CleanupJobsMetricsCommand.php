<?php

namespace Omaralalwi\JobsMetrics\Console\Commands;

use Illuminate\Console\Command;
use Omaralalwi\JobsMetrics\Models\JobsMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CleanupJobsMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs-metrics:cleanup {--days= : Number of days of data to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old job metrics records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $daysToKeep = $this->option('days') ?: config('jobs-metrics.days_to_keep', 30);
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        $this->info("Cleaning up records older than {$cutoffDate->toDateTimeString()}");
        $allRecords = JobsMetric::all();
        $this->info("Initial records count: " . $allRecords->count());
        foreach ($allRecords as $record) {
            $this->info("ID: {$record->id}, Job: {$record->job}, Created: {$record->created_at}");
        }
        $deleted = DB::table('jobs_metrics')->where('created_at', '<', $cutoffDate)->delete();
        $this->info("Deleted {$deleted} old jobs metrics records");
        $remainingRecords = JobsMetric::all();
        $this->info("Remaining records count: " . $remainingRecords->count());
        return 0;
    }
} 