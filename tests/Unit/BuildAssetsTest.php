<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\BuildAssets;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BuildAssetsTest extends TestCase
{
    public function test_minification_preserves_plus_whitespace_and_literal_content(): void
    {
        $css = <<<'CSS'
.x { padding-top: calc(var(--header-height) + 12px); width: calc(100% + 18px); }
.a + .b { margin: 0; }
.literal::before { content: "a + b"; background-image: url("/img/icon+active.svg"); }
CSS;

        $method = new ReflectionMethod(BuildAssets::class, 'minifyCss');
        $output = $method->invoke(new BuildAssets(), $css);

        $this->assertStringContainsString(
            '.x{padding-top:calc(var(--header-height) + 12px);width:calc(100% + 18px)}',
            $output
        );
        $this->assertStringContainsString('.a + .b{margin:0}', $output);
        $this->assertStringContainsString('content:"a + b"', $output);
        $this->assertStringContainsString('url("/img/icon+active.svg")', $output);
    }
}
