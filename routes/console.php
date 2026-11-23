<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publish tenders whose start date has arrived. Checked every ten minutes rather than at a
// few fixed hours, so a missed window cannot hold a tender back for the rest of the day.
// Needs a cron entry on the server calling `artisan schedule:run` every minute —
// see docs/SERVER_SCHEDULER.md. Output goes to storage/logs/auto-publish.log.
Schedule::command('tenders:auto-publish')
    ->everyTenMinutes()
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping(10)   // lock expires in 10 min, so a killed run cannot block the next
    ->appendOutputTo(storage_path('logs/auto-publish.log'));
