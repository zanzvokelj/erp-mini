<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\MonthlyReportJob;
use App\Jobs\ReleaseExpiredReservationsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new MonthlyReportJob)->monthly();

Schedule::job(new ReleaseExpiredReservationsJob)
    ->everyFiveMinutes();
