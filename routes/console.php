<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tokhoom:send-due-reminders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('tokhoom:check-licenses')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('tokhoom:renew-subscriptions')->dailyAt('06:00')->withoutOverlapping();
