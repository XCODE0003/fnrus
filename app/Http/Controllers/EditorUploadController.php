<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShopSettings;
use App\Support\AttachmentSaver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Direct multipart upload endpoint for the custom Trix toolbar buttons
 * (insert image / insert video). Bypasses Livewire's wire.upload
 * pipeline so the JS code does not need a statePath handle — it just
 * POSTs the file and gets a stable /i{hash} URL back.
 *
 * Auth: this route is mounted under the Filament admin panel auth
 * middleware (FilamentSiteAuthBridge), so only authenticated admins
 * can hit it. Max upload size is configurable via
 * shops_settings.editor_max_upload_mb (default 100 MB).
 */
class EditorUploadController
{
    public function upload(Request $request): JsonResponse
    {
        $maxMb = (int) (ShopSettings::getDefault()->editor_max_upload_mb ?? 100);
        $maxMb = max(1, $maxMb);

        $request->validate([
            'file' => ['required', 'file', 'max:' . ($maxMb * 1024)],
        ], [], [
            'file' => 'файл',
        ]);

        $file = $request->file('file');
        if ($file === null || ! $file->isValid()) {
            return response()->json(['ok' => false, 'error' => 'Файл не получен'], 422);
        }

        try {
            // AttachmentSaver expects a Livewire TemporaryUploadedFile —
            // it actually only relies on the Symfony UploadedFile methods
            // (getClientOriginalName/Extension/MimeType/getSize/storeAs),
            // so a regular UploadedFile works the same way. We bypass
            // the type-hint via a tiny shim.
            $hash = self::saveViaAttachmentSaver($file);
        } catch (\Throwable $e) {
            Log::error('EditorUploadController save failed', [
                'error' => $e->getMessage(),
                'mime'  => $file->getClientMimeType(),
                'name'  => $file->getClientOriginalName(),
                'admin' => Auth::id(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Не удалось сохранить файл'], 500);
        }

        if ($hash === null) {
            return response()->json(['ok' => false, 'error' => 'Не удалось сохранить файл'], 500);
        }

        $mime = (string) $file->getClientMimeType();
        $kind = str_starts_with($mime, 'video/')
            ? 'video'
            : (str_starts_with($mime, 'image/') ? 'image' : 'file');

        return response()->json([
            'ok'   => true,
            'hash' => $hash,
            'url'  => url('/i' . $hash),
            'kind' => $kind,
            'mime' => $mime,
            'name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * AttachmentSaver::save() narrows to Livewire TemporaryUploadedFile —
     * we duplicate just the storage + DB insert path here so a plain
     * Symfony UploadedFile from a regular multipart POST also works.
     */
    private static function saveViaAttachmentSaver(\Symfony\Component\HttpFoundation\File\UploadedFile $file): ?string
    {
        $hash = (string) \Illuminate\Support\Str::random(40);
        $ext = strtolower((string) $file->guessExtension());
        if ($ext === '') {
            $ext = strtolower((string) $file->getClientOriginalExtension());
        }
        if ($ext === '') {
            $ext = match ($file->getMimeType()) {
                'image/png'       => 'png',
                'image/jpeg'      => 'jpg',
                'image/webp'      => 'webp',
                'image/gif'       => 'gif',
                'video/mp4'       => 'mp4',
                'video/quicktime' => 'mov',
                'video/webm'      => 'webm',
                default           => 'bin',
            };
        }

        $stored = $file->storeAs('covers', $hash . '.' . $ext, [
            'disk'       => 'public',
            'visibility' => 'public',
        ]);
        if ($stored === false) {
            return null;
        }

        try {
            \DB::table('attachments')->insert([
                'id'          => $hash,
                'title'       => (string) $file->getClientOriginalName(),
                'uid'         => (int) (Auth::id() ?? 0),
                'ext'         => $ext,
                'size'        => (int) $file->getSize(),
                'type'        => 0,
                'uploaded_at' => time(),
            ]);
        } catch (\Throwable $e) {
            \Storage::disk('public')->delete('covers/' . $hash . '.' . $ext);
            throw $e;
        }

        return $hash;
    }
}
