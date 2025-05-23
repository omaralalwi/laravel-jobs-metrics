<?php

namespace Omaralalwi\JobsMetrics\Console\Commands;

use Illuminate\Console\Command;
use Omaralalwi\JobsMetrics\Services\JobsMetricsExporter;
use Omaralalwi\JobsMetrics\Services\JobsMetricsFormatter;
use Omaralalwi\JobsMetrics\Services\JobsMetricsQueryBuilder;

class ExportJobsMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs-metrics:export 
                            {--days=30 : Export data from the last X days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export job metrics data to JSON format';
    
    /**
     * Command options
     */
    protected array $options = [];
    
    /**
     * The query builder service
     */
    protected JobsMetricsQueryBuilder $queryBuilder;
    
    /**
     * The formatter service
     */
    protected JobsMetricsFormatter $formatter;
    
    /**
     * The exporter service
     */
    protected JobsMetricsExporter $exporter;

    /**
     * Create a new command instance.
     *
     * @param JobsMetricsQueryBuilder $queryBuilder
     * @param JobsMetricsFormatter $formatter
     * @param JobsMetricsExporter $exporter
     */
    public function __construct(
        JobsMetricsQueryBuilder $queryBuilder,
        JobsMetricsFormatter $formatter,
        JobsMetricsExporter $exporter
    ) {
        parent::__construct();
        
        $this->queryBuilder = $queryBuilder;
        $this->formatter = $formatter;
        $this->exporter = $exporter;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->parseOptions();
        
        // Set the options on all services
        $this->queryBuilder->setOptions($this->options);
        $this->formatter->setOptions($this->options);
        
        // Export all metrics to a single JSON file
        $this->exportAllMetrics();
        
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
            'limit' => 1000, // High limit to include all jobs
            'sortBy' => 'memory_mb', // Default sort by memory
            'days' => (int) $this->option('days'),
            'startDate' => null,
            'endDate' => null,
        ];
    }
    
    /**
     * Export all metrics to a comprehensive JSON file
     *
     * @return void
     */
    protected function exportAllMetrics(): void
    {
        $this->info("Exporting comprehensive job metrics data to JSON...");
        
        $data = [
            'job_metrics' => $this->getJobMetricsData(),
            'queue_metrics' => $this->getQueueMetricsData(),
            'export_time' => now()->toIso8601String(),
            'period' => "Last {$this->options['days']} days",
        ];
        
        $exportPath = $this->exporter->exportJson('metrics_export', $data);
        $this->info("Metrics data exported to: {$exportPath}");
    }
    
    /**
     * Get formatted job metrics data
     * 
     * @return array
     */
    protected function getJobMetricsData(): array
    {
        $results = $this->queryBuilder->getAggregatedJobMetrics();
        
        return $results->map(function ($item) {
            // Calculate avg per day
            $firstDate = new \Carbon\Carbon($item->first_executed);
            $lastDate = new \Carbon\Carbon($item->last_executed);
            $daysDiff = $firstDate->diffInDays($lastDate) + 1;
            $avgPerDay = $daysDiff > 0 
                ? $this->formatter->formatNumber($item->executions / $daysDiff) 
                : $item->executions;
                
            return [
                'job' => $item->job,
                'executions' => $item->executions,
                'avg_per_day' => $avgPerDay,
                'memory' => [
                    'avg' => $this->formatter->formatNumber($item->avg_memory),
                    'max' => $this->formatter->formatNumber($item->max_memory),
                    'min' => $this->formatter->formatNumber($item->min_memory),
                ],
                'duration' => [
                    'avg' => $this->formatter->formatNumber($item->avg_duration),
                    'max' => $this->formatter->formatNumber($item->max_duration),
                    'min' => $this->formatter->formatNumber($item->min_duration),
                ],
                'first_executed' => $item->first_executed,
                'last_executed' => $item->last_executed
            ];
        })->toArray();
    }
    
    /**
     * Get formatted queue metrics data
     * 
     * @return array
     */
    protected function getQueueMetricsData(): array
    {
        $results = $this->queryBuilder->getQueueMetrics();
        
        return $results->map(function ($item) {
            return [
                'queue' => $item->queue ?: 'default',
                'distinct_jobs' => $item->distinct_jobs,
                'executions' => $item->executions,
                'memory' => [
                    'avg' => $this->formatter->formatNumber($item->avg_memory),
                    'max' => $this->formatter->formatNumber($item->max_memory),
                ],
                'duration' => [
                    'avg' => $this->formatter->formatNumber($item->avg_duration),
                    'max' => $this->formatter->formatNumber($item->max_duration),
                ],
                'first_executed' => $item->first_executed,
                'last_executed' => $item->last_executed
            ];
        })->toArray();
    }
} 