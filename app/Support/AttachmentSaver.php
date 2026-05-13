<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Shared persistence layer for files attached inside Filament RichEditor
 * fields (instructions, policy, delivery copy, etc.).
 *
 * Mirrors AttachmentImageUpload's contract so editor uploads end up in
 * the same `attachments` table the legacy frontend already understands:
 *
 *   1. Generate a 40-char hash.
 *   2. Persist the file to `storage/app/public/covers/{hash}.{ext}`.
 *   3. Insert an `attachments` row with id=hash, ext, size, original
 *      filename, uploaded_at=time().
 *   4. Return the *hash* (used as a stable opaque identifier).
 *
 * The companion `url()` method turns that hash into the public URL
 * served by `StorageController::image` at `/i{hash}` — the same path
 * old templates already use, so attachments render correctly on the
 * customer-facing delivery page without any blade changes.
 */
final class AttachmentSaver
{
    /**
     * Persist a Livewire temporary upload and return the legacy hash.
     * Returns null on failure (file write or DB insert error) — the
     * RichEditor will then show a broken attachment instead of silently
     * pointing at a Livewire temp path that gets garbage-collected.
     */
    public static function save(TemporaryUploadedFile $file): ?string
    {
        $hash = (string) Str::random(40);
        $ext = self::resolveExtension($file);

        try {
            $stored = $file->storeAs('covers', $hash . '.' . $ext, [
                'disk'       => 'public',
                'visibility' => 'public',
            ]);
        } catch (\Throwable $e) {
            Log::error('AttachmentSaver storeAs failed', [
                'error'    => $e->getMessage(),
                'original' => $file->getClientOriginalName(),
            ]);
            return null;
        }

        if ($stored === false) {
            Log::error('AttachmentSaver storeAs returned false', [
                'original' => $file->getClientOriginalName(),
            ]);
            return null;
        }

        try {
            DB::table('attachments')->insert([
                'id'          => $hash,
                'title'       => (string) $file->getClientOriginalName(),
                'uid'         => 0,
                'ext'         => $ext,
                'size'        => (int) $file->getSize(),
                'type'        => 0,
                'uploaded_at' => time(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AttachmentSaver attachments insert failed', [
                'error' => $e->getMessage(),
                'hash'  => $hash,
            ]);
            Storage::disk('public')->delete('covers/' . $hash . '.' . $ext);
            return null;
        }

        Log::info('AttachmentSaver saved', [
            'hash' => $hash,
            'ext'  => $ext,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'name' => $file->getClientOriginalName(),
        ]);

        return $hash;
    }

    /**
     * Return a public URL for a saved attachment. Accepts either the
     * raw hash (preferred — what `save()` returns) or a relative path
     * like `covers/abc.jpg` (just in case the editor passes us one).
     * Returns null if the file does not exist on disk.
     */
    public static function url(?string $fileRef): ?string
    {
        if ($fileRef === null || $fileRef === '') {
            return null;
        }

        if (str_contains($fileRef, '/')) {
            return Storage::disk('public')->exists($fileRef)
                ? Storage::disk('public')->url($fileRef)
                : null;
        }

        $row = DB::table('attachments')->where('id', $fileRef)->first();
        if (! $row || empty($row->ext)) {
            return null;
        }
        $path = 'covers/' . $row->id . '.' . $row->ext;
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return url('/i' . $row->id);
    }

    private static function resolveExtension(TemporaryUploadedFile $file): string
    {
        $ext = strtolower((string) $file->guessExtension());
        if ($ext !== '') {
            return $ext;
        }
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext !== '') {
            return $ext;
        }
        return match ($file->getMimeType()) {
            'image/png'        => 'png',
            'image/jpeg'       => 'jpg',
            'image/webp'       => 'webp',
            'image/gif'        => 'gif',
            'video/mp4'        => 'mp4',
            'video/quicktime'  => 'mov',
            'video/webm'       => 'webm',
            'application/pdf'  => 'pdf',
            default            => 'bin',
        };
    }
}
