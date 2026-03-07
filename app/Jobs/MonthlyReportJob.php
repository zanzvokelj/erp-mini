<?php

namespace App\Jobs;

use App\Services\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MonthlyReportJob implements ShouldQueue
{
    use Queueable;

    public function handle(AnalyticsService $analytics)
    {
        $revenue = $analytics->monthlyRevenue();

        logger()->info('Monthly revenue report generated', [
            'revenue' => $revenue
        ]);
    }
}
