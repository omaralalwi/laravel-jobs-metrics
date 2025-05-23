<?php

namespace Omaralalwi\JobsMetrics\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class JobsMetricsFormatter
{
    /**
     * Command options
     */
    protected array $options = [];
    
    /**
     * Set options for the formatter
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
    
    /**
     * Format a number with 2 decimal places
     *
     * @param mixed $number
     * @return float|int
     */
    public function formatNumber($number): float|int
    {
        return is_numeric($number) ? round((float)$number, 2) : $number;
    }
    
    /**
     * Get headers for queue metrics
     *
     * @return array
     */
    public function getQueueMetricsHeaders(): array
    {
        $headers = [
            'Queue', 
            'Jobs',
            'Executions',
            'Avg Memory (MB)', 
            'Max Memory (MB)',
            'Avg Duration (ms)', 
            'Max Duration (ms)',
            'Last Executed'
        ];
        
        if ($this->options['showTrends']) {
            $headers[] = 'Memory Trend';
            $headers[] = 'Duration Trend';
        }
        
        return $headers;
    }
    
    /**
     * Format queue metrics rows for display
     *
     * @param Collection $results
     * @return array
     */
    public function formatQueueRows(Collection $results): array
    {
        return $results->map(function ($item) {
            $row = [
                $item->queue ?: 'default',
                $item->distinct_jobs,
                $item->executions,
                $this->formatNumber($item->avg_memory),
                $this->formatNumber($item->max_memory),
                $this->formatNumber($item->avg_duration),
                $this->formatNumber($item->max_duration),
                $item->last_executed
            ];
            
            if ($this->options['showTrends'] && isset($item->memory_trend)) {
                $row[] = $item->memory_trend;
                $row[] = $item->duration_trend;
            }
            
            return $row;
        })->toArray();
    }
    
    /**
     * Get headers for job counts
     *
     * @return array
     */
    public function getJobCountsHeaders(): array
    {
        return [
            'Job', 
            'Executions',
            'First Executed',
            'Last Executed',
            'Avg Per Day'
        ];
    }
    
    /**
     * Format job count rows for display
     *
     * @param Collection $results
     * @return array
     */
    public function formatJobCountRows(Collection $results): array
    {
        return $results->map(function ($item) {
            $firstDate = new Carbon($item->first_executed);
            $lastDate = new Carbon($item->last_executed);
            $daysDiff = $firstDate->diffInDays($lastDate) + 1; // +1 to include first day
            $avgPerDay = $daysDiff > 0 ? $this->formatNumber($item->executions / $daysDiff) : $item->executions;
            
            return [
                $item->job,
                $item->executions,
                $item->first_executed,
                $item->last_executed,
                $avgPerDay
            ];
        })->toArray();
    }
    
    /**
     * Get headers for detailed metrics
     *
     * @return array
     */
    public function getDetailedMetricsHeaders(): array
    {
        return ['Job', 'Memory (MB)', 'Duration (ms)', 'Queue', 'Executed At'];
    }
    
    /**
     * Format detailed rows for display
     *
     * @param Collection $results
     * @return array
     */
    public function formatDetailedRows(Collection $results): array
    {
        return $results->map(function ($item) {
            return [
                $item->job,
                $item->memory_mb,
                $item->duration_ms,
                $item->queue ?: 'default',
                $item->created_at
            ];
        })->toArray();
    }
    
    /**
     * Get headers for aggregated metrics
     *
     * @return array
     */
    public function getAggregatedMetricsHeaders(): array
    {
        $headers = [
            'Job', 
            'Executions',
            'Avg Memory (MB)', 
            'Max Memory (MB)',
            'Avg Duration (ms)', 
            'Max Duration (ms)',
            'Last Executed'
        ];
        
        if ($this->options['showTrends']) {
            $headers[] = 'Memory Trend';
            $headers[] = 'Duration Trend';
        }
        
        return $headers;
    }
    
    /**
     * Format aggregated job rows for display
     *
     * @param Collection $results
     * @return array
     */
    public function formatAggregatedJobRows(Collection $results): array
    {
        return $results->map(function ($item) {
            $row = [
                $item->job,
                $item->executions,
                $this->formatNumber($item->avg_memory),
                $this->formatNumber($item->max_memory),
                $this->formatNumber($item->avg_duration),
                $this->formatNumber($item->max_duration),
                $item->last_executed
            ];
            
            if ($this->options['showTrends'] && isset($item->memory_trend)) {
                $row[] = $item->memory_trend;
                $row[] = $item->duration_trend;
            }
            
            return $row;
        })->toArray();
    }
} 