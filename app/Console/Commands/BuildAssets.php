<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Builds minified copies of the hand-edited storefront assets.
 *
 * public/assets/css/style.min.css is edited by hand and, despite its name, is
 * not minified — ~784 KB of readable CSS ships to every visitor and has to be
 * parsed on the phone's CPU. Minifying it in place would destroy the source,
 * so this writes a sibling *.build.css that the layout prefers when present.
 *
 * Run it on deploy, after pulling:
 *   php artisan assets:build
 */
class BuildAssets extends Command
{
    protected $signature = 'assets:build {--clean : remove generated builds instead}';
    protected $description = 'Minify the storefront CSS into a *.build.css served in production';

    /** source => generated */
    private const TARGETS = [
        'assets/css/style.min.css' => 'assets/css/style.build.css',
    ];

    public function handle(): int
    {
        foreach (self::TARGETS as $source => $target) {
            $src = public_path($source);
            $dst = public_path($target);

            if ($this->option('clean')) {
                if (is_file($dst)) {
                    unlink($dst);
                    $this->line("removed  {$target}");
                }
                continue;
            }

            if (!is_file($src)) {
                $this->warn("skip     {$source} (not found)");
                continue;
            }

            $css = file_get_contents($src);
            $min = $this->minifyCss($css);

            file_put_contents($dst, $min);

            $before = strlen($css);
            $after = strlen($min);
            $this->info(sprintf(
                '%s  %s KB -> %s KB (-%d%%)',
                $target,
                number_format($before / 1024, 0),
                number_format($after / 1024, 0),
                $before > 0 ? (int) round(100 - ($after / $before * 100)) : 0
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Conservative CSS minifier: strips comments and collapses whitespace, but
     * never reorders or rewrites declarations, so hand-written hacks and the
     * later-rule-wins ordering this file depends on survive untouched.
     * Content inside strings and url() is left alone.
     */
    private function minifyCss(string $css): string
    {
        $out = '';
        $len = strlen($css);
        $i = 0;

        while ($i < $len) {
            $ch = $css[$i];

            // strings — copy verbatim
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $out .= $ch;
                $i++;
                while ($i < $len) {
                    $c = $css[$i];
                    $out .= $c;
                    $i++;
                    if ($c === '\\' && $i < $len) {      // escaped char
                        $out .= $css[$i];
                        $i++;
                        continue;
                    }
                    if ($c === $quote) {
                        break;
                    }
                }
                continue;
            }

            // comments — drop (but keep /*! ... */ licence blocks)
            if ($ch === '/' && $i + 1 < $len && $css[$i + 1] === '*') {
                $keep = ($i + 2 < $len && $css[$i + 2] === '!');
                $end = strpos($css, '*/', $i + 2);
                $end = $end === false ? $len : $end + 2;
                if ($keep) {
                    $out .= substr($css, $i, $end - $i);
                }
                $i = $end;
                continue;
            }

            // whitespace runs -> single space
            if ($ch === "\n" || $ch === "\r" || $ch === "\t" || $ch === ' ') {
                while ($i < $len && ($css[$i] === "\n" || $css[$i] === "\r" || $css[$i] === "\t" || $css[$i] === ' ')) {
                    $i++;
                }
                $out .= ' ';
                continue;
            }

            $out .= $ch;
            $i++;
        }

        // tidy the spaces around punctuation that CSS never needs
        $out = preg_replace('/\s*([{};:,>~+])\s*/', '$1', $out);
        // ...except the combinators inside :not()/:is() etc. keep working, and
        // a space is still required between simple selectors, which the regex
        // above preserves because it only touches the listed characters.
        $out = str_replace(';}', '}', $out);
        $out = preg_replace('/\s+/', ' ', $out);

        return trim($out);
    }
}
