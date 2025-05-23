<?php

namespace Omaralalwi\JobsMetrics\Console\Commands;

use Illuminate\Console\Command;
use Omaralalwi\JobsMetrics\Services\JobsMetricsFormatter;
use Omaralalwi\JobsMetrics\Services\JobsMetricsQueryBuilder;

class ShowTopJobsCommand extends Command
{
    /**
     * Sort options
     */
    public const SORT_BY_MEMORY = 'memory';
    public const SORT_BY_TIME = 'time';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs-metrics:top
                            {--limit=10 : Number of jobs to display}
                            {--sort=memory : Sort by memory (memory) or duration (time)}
                            {--days=7 : Show data from the last X days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display job metrics with comprehensive statistics';
    
    /**
     * Command options
     */
    protected array $options = [];
    
    /**
     * Date range description for display
     */
    protected string $dateRangeDescription = '';
    
    /**
     * The query builder service
     */
    protected JobsMetricsQueryBuilder $queryBuilder;
    
    /**
     * The formatter service
     */
    protected JobsMetricsFormatter $formatter;

    /**
     * Create a new command instance.
     *
     * @param JobsMetricsQueryBuilder $queryBuilder
     * @param JobsMetricsFormatter $formatter
     */
    public function __construct(
        JobsMetricsQueryBuilder $queryBuilder,
        JobsMetricsFormatter $formatter
    ) {
        parent::__construct();
        
        $this->queryBuilder = $queryBuilder;
        $this->formatter = $formatter;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->parseOptions();
        
        // Set the options on services
        $this->queryBuilder->setOptions($this->options);
        $this->formatter->setOptions($this->options);
        
        $this->displayCommandInfo();
        
        // Display the main metrics dashboard
        $this->displayMetricsDashboard();
        
        return 0;
    }
    
    /**
     * Parse command options and set default values
     *
     * @return void
     */
    protected function parseOptions(): void
    {
        $this->options = [
            'limit' => (int) $this->option('limit'),
            'sortBy' => $this->option('sort') === self::SORT_BY_TIME ? 'duration_ms' : 'memory_mb',
            'sortByRaw' => $this->option('sort'),
            'days' => (int) $this->option('days'),
            'startDate' => null,
            'endDate' => null,
        ];
        
        // Set date range description
        $this->dateRangeDescription = $this->queryBuilder->getDateRangeDescription();
    }
    
    /**
     * Display command information based on the options
     *
     * @return void
     */
    protected function displayCommandInfo(): void
    {
        $days = $this->option('days');
        $sortBy = $this->option('sort');
        $limit = $this->option('limit');
        
        $this->info("Showing job metrics from the last {$days} days");
        $this->info("Sorted by {$sortBy}, limited to {$limit} results");
    }
    
    /**
     * Display comprehensive metrics dashboard
     *
     * @return void
     */
    protected function displayMetricsDashboard(): void
    {
        // Display aggregated metrics by job class
        $this->displayJobMetrics();
        
        // Display queue metrics in summary
        $this->displayQueueSummary();
    }
    
    /**
     * Display aggregated job metrics with additional information
     * 
     * @return void
     */
    protected function displayJobMetrics(): void
    {
        $this->info("\nJOB METRICS:");
        
        $results = $this->queryBuilder->getAggregatedJobMetrics();
        
        // Add average executions per day to the results
        $results = $this->addAvgExecutionsPerDay($results);
        
        $headers = [
            'Job', 
            'Executions',
            'Avg/Day',
            'Avg Memory (MB)', 
            'Max Memory (MB)',
            'Avg Duration (ms)', 
            'Max Duration (ms)',
            'Last Executed'
        ];
        
        $rows = $results->map(function ($item) {
            return [
                $item->job,
                $item->executions,
                $item->avg_per_day,
                $this->formatter->formatNumber($item->avg_memory),
                $this->formatter->formatNumber($item->max_memory),
                $this->formatter->formatNumber($item->avg_duration),
                $this->formatter->formatNumber($item->max_duration),
                $item->last_executed
            ];
        })->toArray();
        
        $this->table($headers, $rows);
    }
    
    /**
     * Display summary of queue statistics
     * 
     * @return void
     */
    protected function displayQueueSummary(): void
    {
        $this->info("\nQUEUE SUMMARY:");
        
        $results = $this->queryBuilder->getQueueMetrics();
        
        $headers = [
            'Queue', 
            'Unique Jobs',
            'Executions',
            'Avg Memory (MB)', 
            'Max Memory (MB)',
            'Avg Duration (ms)', 
            'Max Duration (ms)'
        ];
        
        $rows = $results->map(function ($item) {
            return [
                $item->queue ?: 'default',
                $item->distinct_jobs,
                $item->executions,
                $this->formatter->formatNumber($item->avg_memory),
                $this->formatter->formatNumber($item->max_memory),
                $this->formatter->formatNumber($item->avg_duration),
                $this->formatter->formatNumber($item->max_duration)
            ];
        })->toArray();
        
        $this->table($headers, $rows);
    }
    
    /**
     * Add average executions per day to the results
     * 
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function addAvgExecutionsPerDay($results)
    {
        foreach ($results as $item) {
            $firstDate = new \Carbon\Carbon($item->first_executed);
            $lastDate = new \Carbon\Carbon($item->last_executed);
            $daysDiff = $firstDate->diffInDays($lastDate) + 1; // +1 to include first day
            $item->avg_per_day = $daysDiff > 0 
                ? $this->formatter->formatNumber($item->executions / $daysDiff) 
                : $item->executions;
        }
        
        return $results;
    }
} 