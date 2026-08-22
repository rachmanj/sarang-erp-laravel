<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('whatsapp:poll-messages')->everyMinute();
Schedule::command('whatsapp:send-daily-report')->dailyAt(config('whatsapp.daily_report_time'));
Schedule::command('audit:run')->dailyAt('06:00');
