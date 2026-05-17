<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\QueueManager;
use Throwable;

/**
 * Signal-free queue runner.
 *
 * Hestia disables most pcntl_* functions in admin's php.ini
 * (disable_functions), and Laravel's stock queue:work asks for
 * pcntl_signal via supportsAsyncSignals() — that decision is based on
 * extension_loaded('pcntl'), which is still true, so Worker.php calls
 * pcntl_signal() and dies with "Call to undefined function". The
 * jobs sit in the table forever.
 *
 * This command pops jobs one-by-one (no daemon, no signals) and fires
 * them until the queue is empty or `--max-time` seconds elapse. Cron
 * invokes it every minute.
 */
class QueueDrain extends Command
{
    protected $signature = 'queue:drain {--connection=database} {--queue=default} {--max-time=55 : Stop after N seconds} {--max-jobs=200 : Stop after N jobs}';
    protected $description = 'Drain queued jobs without pcntl signal handling (for shared hosts that disable pcntl_*).';

    public function handle(QueueManager $manager): int
    {
        $connection = (string) $this->option('connection');
        $queue = (string) $this->option('queue');
        $deadline = time() + max(5, (int) $this->option('max-time'));
        $maxJobs = max(1, (int) $this->option('max-jobs'));

        $driver = $manager->connection($connection);
        $processed = 0;
        $failed = 0;

        while (time() < $deadline && $processed < $maxJobs) {
            /** @var Job|null $job */
            $job = $driver->pop($queue);
            if ($job === null) {
                // queue empty — return so the next cron tick handles new arrivals
                break;
            }

            try {
                $job->fire();
                $processed++;
            } catch (Throwable $e) {
                $failed++;
                $job->fail($e);
                $this->error(sprintf('Job failed: %s', $e->getMessage()));
            }
        }

        $this->info(sprintf('drained %d job(s), %d failed', $processed, $failed));
        return self::SUCCESS;
    }
}
