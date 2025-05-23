# Laravel Jobs Metrics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/omaralalwi/laravel-jobs-metrics.svg?style=flat-square)](https://packagist.org/packages/omaralalwi/laravel-jobs-metrics)
[![Total Downloads](https://img.shields.io/packagist/dt/omaralalwi/laravel-jobs-metrics.svg?style=flat-square)](https://packagist.org/packages/omaralalwi/laravel-jobs-metrics)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Tracks job memory consumption and execution time — works with or without Horizon, and enables long-term performance analysis via database logging.

<p align="center">
  <a href="https://omaralalwi.github.io/laravel-jobs-metrics" target="_blank">
    <img src="https://raw.githubusercontent.com/omaralalwi/laravel-jobs-metrics/master/public/images/laravel-jobs-metrics.png" alt="laravel jobs metrics" width="600">
  </a>
</p>

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Configuration](#configuration)
  - [Viewing Job Metrics Report](#viewing-job-metrics-report)
  - [Exporting Job Metrics](#exporting-job-metrics)
  - [Manually Cleaning Up Old Records](#manually-cleaning-up-old-records)
  - [Automatic Cleanup](#automatic-cleanup)
- [Features](#features)
- [Testing](#testing)
- [Credits](#credits)
- [License](#license)
- [Other Packages](#-helpful-open-source-packages--projects)

## Installation

You can install the package via composer:

```bash
composer require omaralalwi/laravel-jobs-metrics
```

After installation, publish the configuration file (optional):

```bash
php artisan vendor:publish --provider="Omaralalwi\JobsMetrics\JobsMetricsServiceProvider" --tag="config"
```

Run the migrations to create the jobs_metrics table:

```bash
php artisan migrate
```

## Usage

### Basic Usage

Add the `HasJobsMetricTracker` trait to your job classes:

```php
<?php

namespace App\Jobs;

use Omaralalwi\JobsMetrics\Traits\HasJobsMetricTracker;

class ProcessPodcast implements ShouldQueue
{
    use HasJobsMetricTracker;

    // Your job implementation...
}
```

That's it! Metrics will be automatically recorded in the `jobs_metrics` table.

### Configuration

The package can be configured via the `config/jobs-metrics.php` file. Here are the available options:

```php
return [
    // Enable or disable metrics tracking globally
    'track_jobs_metrics' => (bool) env('TRACK_JOBS_METRICS', true),
    // Log errors that occur during metrics tracking
    'log_errors' => (bool) env('JOBS_METRICS_LOG_ERRORS', true),
];
```

### Viewing Job Metrics Report

The package provides a simple command to view job metrics:

```bash
# View job metrics with default options
php artisan jobs-metrics:top
# Customize display options
php artisan jobs-metrics:top --limit=20 --sort=time --days=2
```

The command displays a comprehensive info in CLI.

Available options:
- `--limit=N`: Number of jobs to display (default: 10)
- `--sort=memory|time`: Sort by memory usage or execution time (default: memory)
- `--days=N`: Show data from the last N days (default: 7)

### Exporting Job Metrics

You can export all job metrics to a single JSON file:

```bash
# Export job metrics to JSON with default options
php artisan jobs-metrics:export
# Specify a custom time period
php artisan jobs-metrics:export --days=2
```

The export command creates a comprehensive JSON file in the `storage/app/jobs-metrics-export` directory with timestamps in the filename.

### Manually Cleaning Up Old Records

You can manually clean up old metrics records using the provided artisan command:

```bash
php artisan jobs-metrics:cleanup
```
To specify how many days of data to keep:
```bash
php artisan jobs-metrics:cleanup --days=5
```

### Automatic Cleanup

To enable automatic cleanup, ensure your Laravel scheduler is running, and add following scheduler
```php
Schedule::command('jobs-metrics:cleanup')->weekly();
```

---

## Features

- 📊 Records detailed metrics for each job execution (memory usage, duration)
- 🔄 Works with or without Laravel Horizon
- 📋 Per-job statistics including average executions per day
- 📊 Queue-based metrics to identify bottlenecks
- 📁 Export capabilities to JSON for further analysis
- 🧹 Automatic and manual cleanup of old records
- 🎛️ Highly configurable with simple interface
- 🔌 Simple integration with a single trait
- 📋 Tested.

## Testing

```bash
composer test
```

## Credits

- [Omar Alalwi](https://github.com/omaralalwi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## 📚 Helpful Open Source Packages & Projects

### Packages

- <a href="https://github.com/omaralalwi/lexi-translate"><img src="https://raw.githubusercontent.com/omaralalwi/lexi-translate/master/public/images/lexi-translate-banner.jpg" width="26" height="26" style="border-radius:13px;" alt="lexi translate" /> Lexi Translate </a> simplify managing translations for multilingual Eloquent models with power of morph relationships and caching .

- <a href="https://github.com/omaralalwi/Gpdf"><img src="https://raw.githubusercontent.com/omaralalwi/Gpdf/master/public/images/gpdf-banner-bg.jpg" width="26" height="26" style="border-radius:13px;" alt="Gpdf" /> Gpdf </a> Open Source HTML to PDF converter for PHP & Laravel Applications, supports Arabic content out-of-the-box and other languages.

- <a href="https://github.com/omaralalwi/laravel-taxify"><img src="https://raw.githubusercontent.com/omaralalwi/laravel-taxify/master/public/images/taxify.jpg" width="26" height="26" style="border-radius:13px;" alt="laravel Taxify" /> **laravel Taxify** </a> Laravel Taxify provides a set of helper functions and classes to simplify tax (VAT) calculations within Laravel applications.

- <a href="https://github.com/omaralalwi/laravel-deployer"><img src="https://raw.githubusercontent.com/omaralalwi/laravel-deployer/master/public/images/deployer.jpg" width="26" height="26" style="border-radius:13px;" alt="laravel Deployer" /> **laravel Deployer** </a> Streamlined Deployment for Laravel and Node.js apps, with Zero-Downtime and various environments and branches.

- <a href="https://github.com/omaralalwi/laravel-trash-cleaner"><img src="https://raw.githubusercontent.com/omaralalwi/laravel-trash-cleaner/master/public/images/laravel-trash-cleaner.jpg" width="26" height="26" style="border-radius:13px;" alt="laravel Trash Cleaner" /> **laravel Trash Cleaner** </a> clean logs and debug files for debugging packages.

- <a href="https://github.com/omaralalwi/laravel-time-craft"><img src="https://raw.githubusercontent.com/omaralalwi/laravel-time-craft/master/public/images/laravel-time-craft.jpg" width="26" height="26" style="border-radius:13px;" alt="laravel Time Craft" /> **laravel Time Craft** </a> simple trait and helper functions that allow you, Effortlessly manage date and time queries in Laravel apps.

- <a href="https://github.com/omaralalwi/php-builders"><img src="https://repository-images.githubusercontent.com/917404875/c5fbf4c9-d41f-44c6-afc6-0d66cf7f4c4f" width="26" height="26" style="border-radius:13px;" alt="PHP builders" /> **PHP builders** </a> sample php traits to add ability to use builder design patterns with easy in PHP applications.

- <a href="https://github.com/omaralalwi/php-py"> <img src="https://avatars.githubusercontent.com/u/25439498?v=4" width="26" height="26" style="border-radius:13px;" alt="PhpPy - PHP Python" /> **PhpPy - PHP Python** </a> Interact with python in PHP applications.

- <a href="https://github.com/omaralalwi/laravel-py"><img src="https://avatars.githubusercontent.com/u/25439498?v=4" width="26" height="26" style="border-radius:13px;" alt="Laravel Py - Laravel Python" /> **Laravel Py - Laravel Python** </a> interact with python in Laravel applications.

- <a href="https://github.com/deepseek-php/deepseek-php-client"><img src="https://avatars.githubusercontent.com/u/193405629?s=200&v=4" width="26" height="26" style="border-radius:13px;" alt="Deepseek PHP client" /> **deepseek PHP client** </a> robust and community-driven PHP client library for seamless integration with the Deepseek API, offering efficient access to advanced AI and data processing capabilities .

- <a href="https://github.com/deepseek-php/deepseek-laravel"><img src="https://github.com/deepseek-php/deepseek-laravel/blob/master/public/images/laravel%20deepseek%20ai%20banner.jpg?raw=true" width="26" height="26" style="border-radius:13px;" alt="deepseek laravel" /> **deepseek laravel** </a> Laravel wrapper for Deepseek PHP client to seamless deepseek AI API integration with Laravel applications.

- <a href="https://github.com/qwen-php/qwen-php-client"><img src="https://avatars.githubusercontent.com/u/197095442?s=200&v=4" width="26" height="26" style="border-radius:13px;" alt="Qwen PHP client" /> **Qwen PHP client** </a> robust and community-driven PHP client library for seamless integration with the Qwen API .

- <a href="https://github.com/qwen-php/qwen-laravel"><img src="https://github.com/qwen-php/qwen-laravel/blob/master/public/images/laravel%20qwen%20ai%20banner.jpg?raw=true" width="26" height="26" style="border-radius:13px;" alt="qwen laravel" /> **Laravel qwen** </a> wrapper for qwen PHP client to seamless Alibaba qwen AI API integration with Laravel applications..

### Dashboards

- <a href="https://github.com/omaralalwi/laravel-startkit"><img src="https://raw.githubusercontent.com/omaralalwi/laravel-startkit/master/public/screenshots/backend-rtl.png" width="26" height="26" style="border-radius:13px;" alt="Laravel Startkit" /> **Laravel Startkit** </a> Laravel Admin Dashboard, Admin Template with Frontend Template, for scalable Laravel projects.

- <a href="https://github.com/kunafaPlus/kunafa-dashboard-vue"><img src="https://github.com/kunafaPlus/kunafa-dashboard-vue/raw/master/public/screenshots/Home-LTR.png" width="26" height="26" style="border-radius:13px;" alt="Kunafa Dashboard Vue" /> **Kunafa Dashboard Vue** </a> A feature-rich Vue.js 3 dashboard template with multi-language support and full RTL/LTR bidirectional layout capabilities.

### References

- <a href="https://github.com/omaralalwi/clean-code-summary"><img src="https://avatars.githubusercontent.com/u/25439498?v=4" width="26" height="26" style="border-radius:13px;" alt="Clean Code Summary" /> **Clean Code Summary** </a> summarize and notes for books and practices about clean code.

- <a href="https://github.com/omaralalwi/solid-principles-summary"><img src="https://avatars.githubusercontent.com/u/25439498?v=4" width="26" height="26" style="border-radius:13px;" alt="SOLID Principles Summary" /> **SOLID Principles Summary** </a> summarize and notes for books about SOLID Principles.