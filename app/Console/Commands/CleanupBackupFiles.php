<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Move legacy *.bak* / *.orig / *.old files from app/, resources/, routes/
 * to storage/backups/<YYYY-MM-DD-HHMMSS>/ (outside webroot but inside the
 * project so deploys can keep them). Run with `--delete` to actually remove.
 *
 * Usage:
 *   php artisan security:cleanup-backups            # dry-run (default)
 *   php artisan security:cleanup-backups --apply    # move files to archive
 *   php artisan security:cleanup-backups --apply --delete  # delete forever
 */
class CleanupBackupFiles extends Command
{
    protected $signature = 'security:cleanup-backups {--apply : actually move files} {--delete : delete instead of move}';
    protected $description = 'Move or delete leftover *.bak/*.orig/*.old/*.swp source backups out of webroot';

    private const PATTERNS = ['*.bak', '*.bak.*', '*.bak[0-9]', '*.orig', '*.old', '*.swp', '*~'];
    private const SCAN_DIRS = ['app', 'resources', 'routes', 'config', 'database/seeders', 'public'];

    public function handle(): int
    {
        $apply  = (bool) $this->option('apply');
        $delete = (bool) $this->option('delete');
        $base   = base_path();

        $dirs = array_filter(array_map(
            static fn (string $d) => is_dir(base_path($d)) ? base_path($d) : null,
            self::SCAN_DIRS
        ));
        if (empty($dirs)) {
            $this->info('No source directories to scan.');
            return self::SUCCESS;
        }

        $finder = Finder::create()->files()->ignoreVCS(true)->in($dirs);
        foreach (self::PATTERNS as $p) {
            $finder->name($p);
        }

        $moved = 0; $skipped = 0;
        $archive = storage_path('backups/' . date('Y-m-d_His'));

        foreach ($finder as $file) {
            $rel = ltrim(str_replace($base, '', $file->getRealPath()), DIRECTORY_SEPARATOR);
            if (!$apply) {
                $this->line("would handle: {$rel}");
                $moved++;
                continue;
            }
            if ($delete) {
                @unlink($file->getRealPath());
                $this->line("deleted: {$rel}");
                $moved++;
                continue;
            }
            $dest = $archive . DIRECTORY_SEPARATOR . $rel;
            if (!is_dir(dirname($dest))) {
                @mkdir(dirname($dest), 0750, true);
            }
            if (@rename($file->getRealPath(), $dest)) {
                $this->line("moved: {$rel}");
                $moved++;
            } else {
                $this->warn("failed to move: {$rel}");
                $skipped++;
            }
        }

        if (!$apply) {
            $this->info("Dry-run: {$moved} files matched. Re-run with --apply to act.");
        } else {
            $action = $delete ? 'deleted' : "moved to {$archive}";
            $this->info("{$moved} files {$action}; {$skipped} skipped.");
        }

        return self::SUCCESS;
    }
}
