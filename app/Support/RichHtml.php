<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Post-processes HTML saved by the Filament RichEditor (Trix) so that
 * video embeds added through public/js/admin/trix-extensions.js show
 * up as real `<iframe>` / `<video>` tags on the public site.
 *
 * Trix doesn't accept those tags in its document — we work around it
 * by inserting them as Trix attachments with a custom `contentType`
 * of `application/vnd.fnrus.embed.html`. Trix serialises that as
 * `<figure data-trix-attachment="{json}" data-trix-content-type="...">`
 * which is opaque markup on the frontend.
 *
 * `RichHtml::render($html)` replaces those figures with the decoded
 * `content` field from the attachment JSON. Anything else is left
 * untouched. Use via the `@richHtml` Blade directive registered in
 * App\Providers\AppServiceProvider.
 */
class RichHtml
{
    public static function render(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return (string) preg_replace_callback(
            '/<figure\b[^>]*data-trix-attachment="([^"]*)"[^>]*data-trix-content-type="application\/vnd\.fnrus\.embed\.html"[^>]*>.*?<\/figure>/is',
            static function (array $m): string {
                $jsonRaw = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decoded = json_decode($jsonRaw, true);
                $content = is_array($decoded) ? (string) ($decoded['content'] ?? '') : '';
                if ($content === '') {
                    return '';
                }
                // Wrap in a responsive container so iframes don't blow out
                // narrow layouts on mobile.
                return '<div class="fnr-embed">' . $content . '</div>';
            },
            $html
        );
    }
}
