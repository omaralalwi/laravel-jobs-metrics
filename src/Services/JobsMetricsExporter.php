<?php

namespace Omaralalwi\JobsMetrics\Services;

use Illuminate\Support\Facades\File;

class JobsMetricsExporter
{
    /**
     * Export results directly to a JSON file
     *
     * @param string $filename Base name for the file (without extension)
     * @param array $data Data to export
     * @return string The full path to the exported file
     */
    public function exportJson(string $filename, array $data): string
    {
        $exportPath = storage_path('app/jobs-metrics-export');
        
        if (!File::exists($exportPath)) {
            File::makeDirectory($exportPath, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $fullFilename = "{$filename}_{$timestamp}.json";
        $fullPath = "{$exportPath}/{$fullFilename}";
        
        File::put($fullPath, json_encode($data, JSON_PRETTY_PRINT));
        
        return $fullPath;
    }
} 