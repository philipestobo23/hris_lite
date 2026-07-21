<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pull new punches from every active biometric device every 10 minutes.
// withoutOverlapping guards against a slow/offline device stacking runs.
Schedule::command('attendance:sync')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
