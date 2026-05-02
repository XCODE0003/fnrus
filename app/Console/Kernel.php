<?php

namespace App\Console;

use App\Http\Controllers\SenderController;
use App\Http\Controllers\TicketController;
use App\Models\Currency;
use App\Models\Order;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call([Order::class, 'changeStatusByExpiredAt'])->everyMinute();
        $schedule->call([Currency::class, 'cron_convert'])->everyTwoHours();
        $schedule->call([SenderController::class, 'cron_start'])->everyMinute();
        $schedule->call([TicketController::class, 'autoCloseCron'])->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
