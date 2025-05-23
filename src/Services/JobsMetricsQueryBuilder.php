<?php

namespace Omaralalwi\JobsMetrics\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Omaralalwi\JobsMetrics\Models\JobsMetric;

class JobsMetricsQueryBuilder
{
    /**
     * Command options
     */
    protected array $options = [];
    
    /**
     * Date range description for display
     */
    protected string $dateRangeDescription = '';
    
    /**
     * Set options for the query builder
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
        $this->buildDateRangeDescription();
    }
    
    /**
     * Get the date range description
     *
     * @return string
     */
    public function getDateRangeDescription(): string
    {
        return $this->dateRangeDescription;
    }
    
    /**
     * Build the date range description based on options
     *
     * @return void
     */
    protected function buildDateRangeDescription(): void
    {
        if ($this->options['startDate'] && $this->options['endDate']) {
            $this->dateRangeDescription = "between {$this->options['startDate']} and {$this->options['endDate']}";
        } elseif ($this->options['days'] > 0) {
            $this->dateRangeDescription = "from the last {$this->options['days']} days";
        } else {
            $this->dateRangeDescription = "from the entire history";
        }
    }
    
    /**
     * Build the base query with date filters
     *
     * @return Builder
     */
    public function buildBaseQuery(): Builder
    {
        $query = JobsMetric::query();
        
        // Date filtering - prioritize explicit date range over days
        if ($this->options['startDate'] && $this->options['endDate']) {
            $startCarbon = Carbon::parse($this->options['startDate'])->startOfDay();
            $endCarbon = Carbon::parse($this->options['endDate'])->endOfDay();
            $query->whereBetween('created_at', [$startCarbon, $endCarbon]);
        } elseif ($this->options['days'] > 0) {
            $query->where('created_at', '>=', now()->subDays($this->options['days']));
        }
        
        return $query;
    }
    
    /**
     * Get metrics grouped by queue
     *
     * @return Collection
     */
    public function getQueueMetrics(): Collection
    {
        return $this->buildBaseQuery()->select([
            'queue',
            DB::raw('COUNT(*) as executions'),
            DB::raw('COUNT(DISTINCT job) as distinct_jobs'),
            DB::raw('AVG(memory_mb) as avg_memory'),
            DB::raw('MAX(memory_mb) as max_memory'),
            DB::raw('AVG(duration_ms) as avg_duration'),
            DB::raw('MAX(duration_ms) as max_duration'),
            DB::raw('MIN(created_at) as first_executed'),
            DB::raw('MAX(created_at) as last_executed')
        ])
        ->whereNotNull('queue')
        ->groupBy('queue')
        ->orderBy(
            $this->options['sortBy'] === 'memory_mb' ? 'max_memory' : 'max_duration',
            'desc'
        )
        ->get();
    }
    
    /**
     * Get job execution counts
     *
     * @return Collection
     */
    public function getJobCounts(): Collection
    {
        return $this->buildBaseQuery()->select([
            'job',
            DB::raw('COUNT(*) as executions'),
            DB::raw('MIN(created_at) as first_executed'),
            DB::raw('MAX(created_at) as last_executed')
        ])
        ->groupBy('job')
        ->orderBy('executions', 'desc')
        ->get();
    }
    
    /**
     * Get detailed job metrics (individual executions)
     *
     * @return Collection
     */
    public function getDetailedJobMetrics(): Collection
    {
        return $this->buildBaseQuery()
            ->orderBy($this->options['sortBy'], 'desc')
            ->take($this->options['limit'])
            ->get();
    }
    
    /**
     * Get aggregated job metrics by job class
     *
     * @return Collection
     */
    public function getAggregatedJobMetrics(): Collection
    {
        return $this->buildBaseQuery()->select([
            'job',
            DB::raw('COUNT(*) as executions'),
            DB::raw('AVG(memory_mb) as avg_memory'),
            DB::raw('MAX(memory_mb) as max_memory'),
            DB::raw('MIN(memory_mb) as min_memory'),
            DB::raw('AVG(duration_ms) as avg_duration'),
            DB::raw('MAX(duration_ms) as max_duration'),
            DB::raw('MIN(duration_ms) as min_duration'),
            DB::raw('MIN(created_at) as first_executed'),
            DB::raw('MAX(created_at) as last_executed')
        ])
        ->groupBy('job')
        ->orderBy(
            $this->options['sortBy'] === 'memory_mb' ? 'max_memory' : 'max_duration',
            'desc'
        )
        ->take($this->options['limit'])
        ->get();
    }
    
    /**
     * Get previous period data for trend analysis
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string $groupByField
     * @return Collection
     */
    public function getPreviousPeriodData(string $startDate, string $endDate, string $groupByField): Collection
    {
        $query = JobsMetric::query();
        $query->whereBetween('created_at', [$startDate, $endDate]);
        
        return $query->select([
            $groupByField,
            DB::raw('AVG(memory_mb) as avg_memory'),
            DB::raw('AVG(duration_ms) as avg_duration')
        ])
        ->when($groupByField === 'queue', function($query) {
            return $query->whereNotNull('queue');
        })
        ->groupBy($groupByField)
        ->get();
    }
} 