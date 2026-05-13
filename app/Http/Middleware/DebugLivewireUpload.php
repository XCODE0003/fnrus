<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * One-shot diagnostic middleware for Livewire/FilePond uploads.
 *
 * Logs the raw $_FILES error code, mime, size, name, plus the response
 * status so we can find out why the temp-upload preflight rejects an
 * image. Apply globally — it is a no-op for non-upload routes.
 *
 * Remove once image_site uploads are confirmed working.
 */
class DebugLivewireUpload
{
    public function handle(Request $request, Closure $next)
    {
        $isUpload = str_starts_with($request->path(), 'livewire/upload-file')
            || str_starts_with($request->path(), 'livewire/preview-file');

        if (! $isUpload) {
            return $next($request);
        }

        $files = $request->allFiles();
        $meta = [];
        $flatten = static function (array $files, string $prefix = '') use (&$flatten, &$meta) {
            foreach ($files as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                if (is_array($value)) {
                    $flatten($value, $path);
                    continue;
                }
                if ($value instanceof UploadedFile) {
                    $meta[] = [
                        'field'      => $path,
                        'valid'      => $value->isValid(),
                        'error'      => $value->getError(),
                        'error_msg'  => $value->getErrorMessage(),
                        'mime'       => $value->getClientMimeType(),
                        'guess_mime' => method_exists($value, 'getMimeType') ? @$value->getMimeType() : null,
                        'name'       => $value->getClientOriginalName(),
                        'size'       => $value->getSize(),
                        'ext'        => $value->getClientOriginalExtension(),
                    ];
                }
            }
        };
        $flatten($files);

        Log::info('livewire_upload_in', [
            'path'         => $request->path(),
            'content_type' => $request->header('Content-Type'),
            'content_len'  => $request->header('Content-Length'),
            'files'        => $meta,
            'has_csrf'     => (bool) $request->header('X-CSRF-TOKEN'),
            'ua'           => substr((string) $request->header('User-Agent'), 0, 80),
            'ini_upload'   => ini_get('upload_max_filesize'),
            'ini_post'     => ini_get('post_max_size'),
            'tmp_dir'      => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
        ]);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            Log::error('livewire_upload_threw', [
                'class'   => $e::class,
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ]);
            throw $e;
        }

        $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 0;
        $body = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        Log::info('livewire_upload_out', [
            'status' => $status,
            'body'   => substr($body, 0, 600),
        ]);

        return $response;
    }
}
