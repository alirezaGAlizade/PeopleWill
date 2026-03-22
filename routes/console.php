<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:close-expired-windows')->dailyAt('00:01');

Schedule::command('questions:evaluate-response-deadlines')->hourly();
Schedule::command('questions:evaluate-validation-windows')->hourly();
Schedule::command('questions:evaluate-remediation-windows')->hourly();
