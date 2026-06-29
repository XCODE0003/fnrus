<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lossy-but-faithful WebP encoder built on GD.
 *
 * Used both at upload time (AttachmentImageUpload) and by the batch
 * `images:webp` command. Transparency is always preserved, so a PNG with
 * an alpha background stays transparent after conversion. Quality is tuned
 * per source type: photos (JPEG) compress hard, UI graphics (PNG) stay
 * crisp.
 */
class ImageWebp
{
    /** Raster source extensions we convert. (GIF excluded — keep animation.) */
    public const CONVERTIBLE = ['png', 'jpg', 'jpeg'];

    public static function supported(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromstring');
    }

    /**
     * Encode the image at $srcPath to WebP at $destPath.
     *
     * @param  int|null  $quality  0-100; null = auto (JPEG 82, PNG 90).
     * @return bool  true only when a non-empty WebP file was written.
     */
    public static function encode(string $srcPath, string $destPath, ?int $quality = null): bool
    {
        if (! self::supported() || ! is_file($srcPath)) {
            return false;
        }

        $raw = @file_get_contents($srcPath);
        if ($raw === false || $raw === '') {
            return false;
        }

        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return false;
        }

        // Palette PNG/GIF -> truecolor so alpha survives; then keep the
        // alpha channel on export (no flattening onto black/white).
        @imagepalettetotruecolor($img);
        @imagealphablending($img, false);
        @imagesavealpha($img, true);

        if ($quality === null) {
            $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
            $quality = in_array($ext, ['jpg', 'jpeg'], true) ? 82 : 90;
        }
        $quality = max(0, min(100, $quality));

        $ok = @imagewebp($img, $destPath, $quality);
        // (imagedestroy is a no-op since PHP 8.0 — $img is freed on scope exit.)

        // GD can return true while leaving a 0-byte file on partial failure.
        if (! $ok || ! is_file($destPath) || filesize($destPath) === 0) {
            if (is_file($destPath)) {
                @unlink($destPath);
            }
            return false;
        }

        return true;
    }
}
