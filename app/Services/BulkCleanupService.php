<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Hard-delete operations for large tables. Each method:
 *  - validates the limit (1..MAX_PER_CALL) up front,
 *  - chunks deletes (CHUNK rows at a time) to keep transactions short,
 *  - returns the number of rows actually removed.
 *
 * Files (Attach + on-disk blob) are removed in a single transaction
 * pair so the DB row and the storage file disappear together; on
 * filesystem failure the DB row is still removed (storage may be
 * stale, but admin audit reflects the actual DB state).
 */
class BulkCleanupService
{
    public const MAX_PER_CALL = 5000;
    private const CHUNK = 200;

    public const ORDER_TYPE_EXPIRED   = 'expired';   // status = 4
    public const ORDER_TYPE_PAID      = 'paid';      // status = 2
    public const ORDER_TYPE_CANCELLED = 'cancelled'; // status = 3

    public const FILE_TYPE_COVERS  = 'covers';
    public const FILE_TYPE_AVATARS = 'avatars';
    public const FILE_TYPE_FILES   = 'files';

    public const VALID_ORDER_TYPES = [
        self::ORDER_TYPE_EXPIRED,
        self::ORDER_TYPE_PAID,
        self::ORDER_TYPE_CANCELLED,
    ];

    public const VALID_FILE_TYPES = [
        self::FILE_TYPE_COVERS,
        self::FILE_TYPE_AVATARS,
        self::FILE_TYPE_FILES,
    ];

    public function cleanupOrders(string $type, int $limit): int
    {
        $this->assertLimit($limit);
        if (!in_array($type, self::VALID_ORDER_TYPES, true)) {
            throw new \InvalidArgumentException('Unknown order type: ' . $type);
        }
        $statusValue = match ($type) {
            self::ORDER_TYPE_EXPIRED   => 4,
            self::ORDER_TYPE_CANCELLED => 3,
            default                    => 2, // paid
        };

        return $this->deleteOldestInChunks(
            'orders',
            ['status' => $statusValue],
            $limit,
            'created_at'
        );
    }

    public function cleanupCoupons(int $limit): int
    {
        $this->assertLimit($limit);
        return $this->deleteOldestInChunks('coupons', [], $limit, 'created_at');
    }

    public function cleanupExports(int $limit): int
    {
        $this->assertLimit($limit);
        if (!\Schema::hasTable('materials_exports')) {
            return 0;
        }
        return $this->deleteOldestInChunks('materials_exports', [], $limit, 'created_at');
    }

    /**
     * @return array{rows: int, files: int, missing: int}
     */
    public function cleanupFiles(string $type, int $limit): array
    {
        $this->assertLimit($limit);
        if (!in_array($type, self::VALID_FILE_TYPES, true)) {
            throw new \InvalidArgumentException('Unknown file type: ' . $type);
        }

        $rowsDeleted = 0;
        $filesDeleted = 0;
        $filesMissing = 0;
        $disk = Storage::disk('public');

        $remaining = $limit;
        while ($remaining > 0) {
            $batch = min(self::CHUNK, $remaining);
            $rows = DB::table('attachments')
                ->where('type', $type)
                ->orderBy('id')
                ->limit($batch)
                ->get(['id', 'ext']);

            if ($rows->isEmpty()) break;

            $ids = $rows->pluck('id')->all();

            DB::transaction(function () use ($ids, &$rowsDeleted) {
                $rowsDeleted += DB::table('attachments')->whereIn('id', $ids)->delete();
            });

            foreach ($rows as $r) {
                $relative = $type . '/' . $r->id . '.' . $r->ext;
                if ($disk->exists($relative)) {
                    if ($disk->delete($relative)) {
                        $filesDeleted++;
                    }
                } else {
                    $filesMissing++;
                }
            }

            $remaining -= count($ids);
            if (count($ids) < $batch) break;
        }

        return ['rows' => $rowsDeleted, 'files' => $filesDeleted, 'missing' => $filesMissing];
    }

    /**
     * Generic: delete oldest N rows matching $where, returning count removed.
     */
    private function deleteOldestInChunks(string $table, array $where, int $limit, string $orderBy): int
    {
        if (!\Schema::hasTable($table)) {
            return 0;
        }

        $deleted = 0;
        $remaining = $limit;

        while ($remaining > 0) {
            $batch = min(self::CHUNK, $remaining);

            $query = DB::table($table);
            foreach ($where as $col => $val) {
                $query->where($col, $val);
            }

            $ids = $query->orderBy($orderBy)
                ->limit($batch)
                ->pluck('id');

            if ($ids->isEmpty()) break;

            DB::transaction(function () use ($table, $ids, &$deleted) {
                $deleted += DB::table($table)->whereIn('id', $ids->all())->delete();
            });

            $remaining -= $ids->count();
            if ($ids->count() < $batch) break;
        }

        Log::info('BulkCleanupService: deleted', [
            'table' => $table, 'where' => $where, 'deleted' => $deleted,
        ]);

        return $deleted;
    }

    private function assertLimit(int $limit): void
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('limit must be >= 1');
        }
        if ($limit > self::MAX_PER_CALL) {
            throw new \InvalidArgumentException('limit must be <= ' . self::MAX_PER_CALL);
        }
    }
}
