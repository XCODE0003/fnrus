<?php

namespace Tests\Unit;

use App\Services\TelegramPublisher;
use PHPUnit\Framework\TestCase;

class TelegramPublisherTest extends TestCase
{
    private TelegramPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publisher = new TelegramPublisher();
    }

    public function test_applies_simple_placeholders(): void
    {
        $out = $this->publisher->applyPlaceholders(
            'Игра: {game}, Софт: {product}, Статус: {status}',
            ['game' => 'CS2', 'product' => 'Aimbot Pro', 'status' => 'Рекомендуем']
        );
        $this->assertSame('Игра: CS2, Софт: Aimbot Pro, Статус: Рекомендуем', $out);
    }

    public function test_unknown_placeholders_are_left_intact(): void
    {
        $out = $this->publisher->applyPlaceholders('Hello {unknown}', ['game' => 'X']);
        $this->assertSame('Hello {unknown}', $out);
    }

    public function test_sanitizer_keeps_telegram_whitelist(): void
    {
        $html = '<b>bold</b> <i>italic</i> <u>underline</u> <s>strike</s>'
              . ' <code>code</code> <pre>pre</pre> <a href="https://t.me/test">link</a>';
        $out = $this->publisher->sanitizeForTelegram($html);
        $this->assertSame(
            '<b>bold</b> <i>italic</i> <u>underline</u> <s>strike</s>'
              . ' <code>code</code> <pre>pre</pre> <a href="https://t.me/test">link</a>',
            $out
        );
    }

    public function test_sanitizer_converts_strong_and_em(): void
    {
        $out = $this->publisher->sanitizeForTelegram('<strong>x</strong> <em>y</em>');
        $this->assertSame('<b>x</b> <i>y</i>', $out);
    }

    public function test_sanitizer_strips_unknown_tags(): void
    {
        $out = $this->publisher->sanitizeForTelegram('<script>alert(1)</script><span>x</span>');
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringNotContainsString('<span>', $out);
        $this->assertStringContainsString('x', $out);
    }

    public function test_sanitizer_turns_paragraphs_into_newlines(): void
    {
        $out = $this->publisher->sanitizeForTelegram('<p>line one</p><p>line two</p>');
        $this->assertSame("line one\nline two", $out);
    }

    public function test_sanitizer_handles_br(): void
    {
        $this->assertSame("a\nb", $this->publisher->sanitizeForTelegram('a<br>b'));
        $this->assertSame("a\nb", $this->publisher->sanitizeForTelegram('a<br/>b'));
        $this->assertSame("a\nb", $this->publisher->sanitizeForTelegram('a<br />b'));
    }

    public function test_sanitizer_renders_lists_with_bullets(): void
    {
        $out = $this->publisher->sanitizeForTelegram('<ul><li>one</li><li>two</li></ul>');
        $this->assertStringContainsString('• one', $out);
        $this->assertStringContainsString('• two', $out);
    }

    public function test_sanitizer_collapses_excess_blank_lines(): void
    {
        $out = $this->publisher->sanitizeForTelegram("<p>a</p><p></p><p></p><p>b</p>");
        $this->assertSame("a\n\nb", $out);
    }

    public function test_sanitizer_handles_emoji(): void
    {
        $out = $this->publisher->sanitizeForTelegram('<p>Status: ♻️ updated</p>');
        $this->assertSame('Status: ♻️ updated', $out);
    }
}
