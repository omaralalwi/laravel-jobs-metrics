<?php

namespace Omaralalwi\JobsMetrics\Models;

use Illuminate\Database\Eloquent\Model;

class JobsMetric extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'jobs_metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'job',
        'queue',
        'duration_ms',
        'memory_mb',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration_ms' => 'float',
        'memory_mb' => 'float',
    ];
}
