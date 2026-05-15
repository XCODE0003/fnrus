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
        // withoutOverlapping: рассылка на 40k подписчиков идёт ~20+ мин,
        // без этого cron каждую минуту запускал бы новые параллельные
        // start_sender'ы и плодил зависших.
        $schedule->call([SenderController::class, 'cron_start'])
            ->name('senders_cron_start')
            ->everyMinute()
            ->withoutOverlapping(30);
        $schedule->call([TicketController::class, 'autoCloseCron'])->everyMinute();

        // Подбираем рассылки, чей status=4 повис без увеличения счётчиков
        // (cron упал / PHP вылетел по timeout). Через 60 минут без
        // прогресса возвращаем в очередь.
        $schedule->call(function () {
            \DB::table('senders')
                ->where('status', 4)
                ->where('started_at', '<', time() - 3600)
                ->update(['status' => 1]);
        })->everyFifteenMinutes();

        // Cron-based queue worker — поднимаем воркер на 55 секунд, потом
        // выходим, чтобы следующий cron tick подхватил. Так email-рассылка
        // (job SendBroadcastEmail) запускается асинхронно без supervisord.
        $schedule->command('queue:work --stop-when-empty --max-time=55 --tries=3 --sleep=2')
            ->name('queue_worker')
            ->everyMinute()
            ->withoutOverlapping(2)
            ->runInBackground();

        // Подбираем счётчики email-рассылок (recipients_sent / failed)
        // — статус STATUS_SENT когда все письма обработаны.
        $schedule->call(function () {
            $ids = \DB::table('email_broadcasts')
                ->whereIn('status', [\App\Models\EmailBroadcast::STATUS_QUEUED, \App\Models\EmailBroadcast::STATUS_SENDING])
                ->pluck('id');
            $svc = app(\App\Services\EmailBroadcastService::class);
            foreach ($ids as $id) {
                try { $svc->tickStatus((int) $id); } catch (\Throwable $e) {}
            }
        })->everyMinute();
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
