<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FileUpload that integrates with the legacy `attachments` table.
 *
 * On save: stores the file at storage/app/public/covers/{hash}.{ext},
 * inserts a row into `attachments`, and writes only the hash to the
 * column — so the frontend's existing `/i{hash}` route (handled by
 * App\Http\Controllers\StorageController::image) keeps working.
 *
 * On hydrate: turns the stored hash back into a relative path
 * `covers/{hash}.{ext}` so Filament's preview can render via the
 * public disk.
 *
 * Use as a drop-in replacement for FileUpload::make() wherever the
 * column stores legacy attachment hashes (Product.image_site,
 * Product.image, Category.image_site, etc).
 *
 * Multiple-mode is also supported (gallery columns store a JSON
 * array of hashes).
 */
class AttachmentImageUpload
{
    public static function make(string $name): FileUpload
    {
        $upload = FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory('covers')
            ->visibility('public')
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
            ->maxSize(5120);

        return $upload
            ->getUploadedFileNameForStorageUsing(function (UploadedFile $file): string {
                // Generate legacy-style hash (40 chars, [a-zA-Z0-9]) — same shape
                // as Storage::hashName() but stable so we can insert before move.
                $hash = (string) Str::random(40);
                return $hash . '.' . $file->extension();
            })
            ->saveUploadedFileUsing(function (UploadedFile $file, FileUpload $component) {
                $hash = (string) Str::random(40);
                $ext = (string) $file->extension();
                $stored = $file->storeAs('covers', $hash . '.' . $ext, [
                    'disk' => 'public',
                    'visibility' => 'public',
                ]);
                if ($stored === false) {
                    return null;
                }

                DB::table('attachments')->insert([
                    'id'          => $hash,
                    'title'       => (string) $file->getClientOriginalName(),
                    'uid'         => 0,
                    'ext'         => $ext,
                    'size'        => (int) $file->getSize(),
                    'type'        => 0,
                    'uploaded_at' => time(),
                ]);

                return $hash;
            })
            ->afterStateHydrated(function (FileUpload $component, $state): void {
                if ($state === null || $state === '') {
                    return;
                }

                if ($component->isMultiple()) {
                    $hashes = is_array($state)
                        ? $state
                        : (array) (is_string($state) ? json_decode($state, true) ?: [$state] : []);
                    $paths = [];
                    foreach ($hashes as $h) {
                        $h = (string) $h;
                        if ($h === '') { continue; }
                        $path = self::resolvePath($h);
                        if ($path !== null) {
                            $paths[] = $path;
                        }
                    }
                    $component->state($paths);
                    return;
                }

                $path = self::resolvePath((string) $state);
                if ($path !== null) {
                    // FileUpload normalises state to an array internally even
                    // for single-file mode — passing a bare string blows up
                    // BaseFileUpload::getStateToDehydrate's foreach.
                    $component->state([$path]);
                }
            })
            ->dehydrateStateUsing(function ($state) use ($upload) {
                if ($state === null || $state === '' || $state === []) {
                    return null;
                }

                $extract = static function ($pathOrHash): ?string {
                    $pathOrHash = (string) $pathOrHash;
                    if ($pathOrHash === '') { return null; }
                    if (! str_contains($pathOrHash, '/') && ! str_contains($pathOrHash, '.')) {
                        return $pathOrHash;
                    }
                    $base = pathinfo($pathOrHash, PATHINFO_FILENAME);
                    return $base !== '' ? $base : null;
                };

                if (is_array($state)) {
                    $hashes = array_values(array_filter(array_map($extract, $state), fn ($v) => $v !== null && $v !== ''));
                    return $hashes === [] ? null : json_encode($hashes);
                }

                return $extract($state);
            })
            ->deleteUploadedFileUsing(function (string $file): void {
                // file = "covers/{hash}.{ext}" if user just uploaded; or raw "covers/x.jpg"
                $hash = pathinfo($file, PATHINFO_FILENAME);
                if ($hash === '') { return; }
                $row = DB::table('attachments')->where('id', $hash)->first();
                if ($row && ! empty($row->ext)) {
                    Storage::disk('public')->delete('covers/' . $row->id . '.' . $row->ext);
                    DB::table('attachments')->where('id', $row->id)->delete();
                }
            });
    }

    /**
     * Multi-image variant that stores a JSON array of hashes
     * (matches Product.gallery's existing format).
     */
    public static function makeMultiple(string $name): FileUpload
    {
        return self::make($name)->multiple()->reorderable();
    }

    /**
     * Resolve a legacy attachment hash to a filename-relative-to-disk
     * suitable for FilePond preview (e.g. "covers/abc.jpg").
     */
    private static function resolvePath(string $hash): ?string
    {
        if ($hash === '') {
            return null;
        }
        // Path can sometimes already be relative; bail.
        if (str_contains($hash, '/')) {
            return Storage::disk('public')->exists($hash) ? $hash : null;
        }
        $row = DB::table('attachments')->where('id', $hash)->first();
        if (! $row || empty($row->ext)) {
            return null;
        }
        $path = 'covers/' . $row->id . '.' . $row->ext;
        return Storage::disk('public')->exists($path) ? $path : null;
    }
}
