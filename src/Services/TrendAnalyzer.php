<?php

namespace Omaralalwi\JobsMetrics\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TrendAnalyzer
{
    /**
     * Command options
     */
    protected array $options = [];
    
    /**
     * Previous period data for trend calculation
     */
    protected array $previousPeriodData = [];
    
    /**
     * Previous period date range
     */
    protected ?string $previousPeriodDescription = null;
    
    /**
     * Trend emoji indicators
     */
    protected array $trendIndicators = [
        'up' => '↑',
        'down' => '↓',
        'stable' => '→'
    ];
    
    /**
     * Query builder service
     */
    protected JobsMetricsQueryBuilder $queryBuilder;
    
    /**
     * Create a new trend analyzer instance.
     *
     * @param JobsMetricsQueryBuilder $queryBuilder
     */
    public function __construct(JobsMetricsQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
    
    /**
     * Set options for the analyzer
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
        
        if ($options['showTrends'] && !$options['detailed']) {
            $this->loadPreviousPeriodData();
        }
    }
    
    /**
     * Get the previous period description
     *
     * @return string|null
     */
    public function getPreviousPeriodDescription(): ?string
    {
        return $this->previousPeriodDescription;
    }
    
    /**
     * Load previous period data for trend calculation
     *
     * @return void
     */
    protected function loadPreviousPeriodData(): void
    {
        $previousPeriodStartDate = null;
        $previousPeriodEndDate = null;
        
        if ($this->options['startDate'] && $this->options['endDate']) {
            $currentPeriodDays = Carbon::parse($this->options['startDate'])->diffInDays(Carbon::parse($this->options['endDate'])) + 1;
            $previousPeriodEndDate = Carbon::parse($this->options['startDate'])->subDay();
            $previousPeriodStartDate = $previousPeriodEndDate->copy()->subDays($currentPeriodDays - 1);
        } elseif ($this->options['days'] > 0) {
            $previousPeriodEndDate = now()->subDays($this->options['days']);
            $previousPeriodStartDate = $previousPeriodEndDate->copy()->subDays($this->options['days']);
        }
        
        if ($previousPeriodStartDate && $previousPeriodEndDate) {
            $groupByField = $this->options['groupByQueues'] ? 'queue' : 'job';
            
            $previousData = $this->queryBuilder->getPreviousPeriodData(
                $previousPeriodStartDate->format('Y-m-d H:i:s'),
                $previousPeriodEndDate->format('Y-m-d H:i:s'),
                $groupByField
            );
            
            // Index by job or queue name
            foreach ($previousData as $item) {
                $key = $this->options['groupByQueues'] ? $item->queue : $item->job;
                $this->previousPeriodData[$key] = [
                    'avg_memory' => $item->avg_memory,
                    'avg_duration' => $item->avg_duration
                ];
            }
            
            $this->previousPeriodDescription = "{$previousPeriodStartDate->format('Y-m-d')} to {$previousPeriodEndDate->format('Y-m-d')}";
        }
    }
    
    /**
     * Add trend data to job metrics results
     *
     * @param Collection $results
     * @return void
     */
    public function addTrendDataForJobs(Collection $results): void
    {
        foreach ($results as $item) {
            $item->memory_trend = $this->getTrendIndicator($item->job, 'avg_memory', $item->avg_memory);
            $item->duration_trend = $this->getTrendIndicator($item->job, 'avg_duration', $item->avg_duration);
        }
    }
    
    /**
     * Add trend data to queue metrics results
     *
     * @param Collection $results
     * @return void
     */
    public function addTrendDataForQueues(Collection $results): void
    {
        foreach ($results as $item) {
            $item->memory_trend = $this->getTrendIndicator($item->queue, 'avg_memory', $item->avg_memory);
            $item->duration_trend = $this->getTrendIndicator($item->queue, 'avg_duration', $item->avg_duration);
        }
    }
    
    /**
     * Get a trend indicator by comparing current value with previous period
     *
     * @param string $key The job or queue name
     * @param string $metric The metric to compare (avg_memory or avg_duration)
     * @param float $currentValue Current value of the metric
     * @return string Trend indicator
     */
    protected function getTrendIndicator(string $key, string $metric, float $currentValue): string
    {
        if (!isset($this->previousPeriodData[$key]) || !isset($this->previousPeriodData[$key][$metric])) {
            return 'N/A';
        }
        
        $previousValue = $this->previousPeriodData[$key][$metric];
        
        // Skip if either value is zero to avoid division by zero
        if ($previousValue == 0 || $currentValue == 0) {
            return 'N/A';
        }
        
        $percentChange = (($currentValue - $previousValue) / $previousValue) * 100;
        
        // Only show significant changes (more than 5%)
        if (abs($percentChange) < 5) {
            return $this->trendIndicators['stable'] . ' (0%)';
        } elseif ($percentChange > 0) {
            return $this->trendIndicators['up'] . ' (+' . round($percentChange) . '%)';
        } else {
            return $this->trendIndicators['down'] . ' (' . round($percentChange) . '%)';
        }
    }
} 