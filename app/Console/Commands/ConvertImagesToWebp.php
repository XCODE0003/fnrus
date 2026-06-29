<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ImageWebp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Batch-convert existing cover images (png/jpg/jpeg) to WebP.
 *
 * For each converted image we:
 *   1. write covers/{hash}.webp (transparency preserved),
 *   2. only keep it when it's smaller than the original,
 *   3. update attachments.ext = 'webp' (+ new size) — this is the "path"
 *      stored in the DB, since product/category columns hold only the hash
 *      and the file is resolved as {hash}.{ext},
 *   4. delete the original {hash}.{ext} file.
 *
 * Idempotent: rows already on ext=webp are not matched. Safe to re-run.
 */
class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:webp
        {--dry-run : Report what would change without writing anything}
        {--limit=0 : Max attachments to process (0 = all)}
        {--quality= : Override WebP quality 0-100 (default: JPEG 82 / PNG 90)}';

    protected $description = 'Convert existing cover images (png/jpg) to WebP, update attachments.ext and delete originals';

    public function handle(): int
    {
        if (! ImageWebp::supported()) {
            $this->error('GD WebP support is not available on this PHP build. Aborting.');
            return self::FAILURE;
        }

        $dry     = (bool) $this->option('dry-run');
        $limit   = (int) $this->option('limit');
        $quality = $this->option('quality') !== null ? (int) $this->option('quality') : null;
        $disk    = Storage::disk('public');

        $base = DB::table('attachments')
            ->whereIn('ext', ImageWebp::CONVERTIBLE)
            ->where('type', 0);

        $total = (clone $base)->count();
        if ($limit > 0) {
            $total = min($total, $limit);
        }
        if ($total === 0) {
            $this->info('Nothing to convert — no png/jpg cover attachments found.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . "Converting {$total} image(s) to WebP…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $converted = 0; $skipped = 0; $failed = 0; $missing = 0; $saved = 0;
        $processed = 0;

        $base->orderBy('id')->chunkById(200, function ($rows) use (
            &$converted, &$skipped, &$failed, &$missing, &$saved, &$processed,
            $disk, $dry, $quality, $limit, $bar
        ) {
            foreach ($rows as $row) {
                if ($limit > 0 && $processed >= $limit) {
                    return false; // stop chunking
                }
                $processed++;
                $bar->advance();

                $srcAbs  = $disk->path('covers/' . $row->id . '.' . $row->ext);
                $webpAbs = $disk->path('covers/' . $row->id . '.webp');

                if (! is_file($srcAbs)) {
                    $missing++;
                    continue;
                }

                try {
                    if (! ImageWebp::encode($srcAbs, $webpAbs, $quality)) {
                        $failed++;
                        continue;
                    }

                    $oldSize = (int) filesize($srcAbs);
                    $newSize = (int) filesize($webpAbs);

                    // Don't keep a conversion that doesn't actually save space.
                    if ($newSize <= 0 || $newSize >= $oldSize) {
                        @unlink($webpAbs);
                        $skipped++;
                        continue;
                    }

                    $saved += ($oldSize - $newSize);
                    $converted++;

                    if ($dry) {
                        @unlink($webpAbs); // leave disk + DB untouched
                        continue;
                    }

                    DB::table('attachments')->where('id', $row->id)
                        ->update(['ext' => 'webp', 'size' => $newSize]);
                    @unlink($srcAbs);
                } catch (\Throwable $e) {
                    if (is_file($webpAbs)) {
                        @unlink($webpAbs);
                    }
                    $failed++;
                    \Log::warning('images:webp failed for one attachment', [
                        'id'    => $row->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return null;
        }, 'id', 'id');

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            '%sDone. Converted: %d  Skipped(no-gain): %d  Missing files: %d  Failed: %d  Saved: %.2f MB',
            $dry ? '[DRY RUN] ' : '',
            $converted, $skipped, $missing, $failed, $saved / 1048576
        ));

        if ($dry) {
            $this->comment('No files or DB rows were changed. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
