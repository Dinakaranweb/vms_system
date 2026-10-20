<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-publish tenders whose start date has arrived (checks 4x daily in IST: 12am, 8am, 8pm, 9pm)
Schedule::command('tenders:auto-publish')->dailyAt('00:00')->timezone('Asia/Kolkata');
Schedule::command('tenders:auto-publish')->dailyAt('08:00')->timezone('Asia/Kolkata');
Schedule::command('tenders:auto-publish')->dailyAt('20:00')->timezone('Asia/Kolkata');
Schedule::command('tenders:auto-publish')->dailyAt('21:00')->timezone('Asia/Kolkata');
