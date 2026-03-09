<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run forecasting daily at 1:00 AM
Schedule::command('forecast:run')->dailyAt('01:00');

// Run database backup daily at 2:00 AM
Schedule::command('backup:run')->dailyAt('02:00');

// Check attendance alerts daily at 8:00 AM
Schedule::command('alerts:check')->dailyAt('08:00');
