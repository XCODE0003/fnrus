<?php

namespace Tests\Unit;

use App\Services\EmailBroadcastService;
use PHPUnit\Framework\TestCase;

class EmailBroadcastServiceTest extends TestCase
{
    private EmailBroadcastService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new EmailBroadcastService();
    }

    public function test_personalise_substitutes_known_placeholders(): void
    {
        $out = $this->svc->personalise(
            'Привет, {email}!',
            ['email' => 'user@example.com']
        );
        $this->assertSame('Привет, user@example.com!', $out);
    }

    public function test_personalise_leaves_unknown_placeholders(): void
    {
        $out = $this->svc->personalise('Hello {unknown}', ['email' => 'x@y.z']);
        $this->assertSame('Hello {unknown}', $out);
    }

    public function test_sanitize_strips_script_blocks_with_content(): void
    {
        $out = $this->svc->sanitize('<p>OK</p><script>alert(1)</script><p>End</p>');
        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('alert(1)', $out);
        $this->assertStringContainsString('OK', $out);
        $this->assertStringContainsString('End', $out);
    }

    public function test_sanitize_strips_iframes(): void
    {
        $out = $this->svc->sanitize('<iframe src="evil.html"></iframe><p>ok</p>');
        $this->assertStringNotContainsString('iframe', $out);
        $this->assertStringContainsString('ok', $out);
    }

    public function test_sanitize_removes_event_handlers(): void
    {
        $out = $this->svc->sanitize('<a href="x" onclick="evil()">link</a>');
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringContainsString('href="x"', $out);
    }

    public function test_sanitize_strips_javascript_protocol(): void
    {
        $out = $this->svc->sanitize('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript:', $out);
    }

    public function test_sanitize_keeps_safe_html_subset(): void
    {
        $html = '<p>Hello</p><p><strong>Bold</strong> and <em>italic</em></p>'
              . '<ul><li>one</li><li>two</li></ul>'
              . '<a href="https://example.com">link</a>';
        $out = $this->svc->sanitize($html);

        $this->assertStringContainsString('<strong>Bold</strong>', $out);
        $this->assertStringContainsString('<em>italic</em>', $out);
        $this->assertStringContainsString('<li>one</li>', $out);
        $this->assertStringContainsString('href="https://example.com"', $out);
    }

    public function test_sanitize_truncates_oversized_input(): void
    {
        $huge = str_repeat('<p>x</p>', 100_000); // ~700KB
        $out = $this->svc->sanitize($huge);
        $this->assertLessThanOrEqual(EmailBroadcastService::HTML_LIMIT, mb_strlen($out));
    }

    public function test_sanitize_handles_form_tags(): void
    {
        $out = $this->svc->sanitize('<form action="x"><input name="y"></form><p>ok</p>');
        $this->assertStringNotContainsString('<form', $out);
        $this->assertStringNotContainsString('<input', $out);
        $this->assertStringContainsString('ok', $out);
    }

    public function test_sanitize_strips_style_with_content(): void
    {
        $out = $this->svc->sanitize('<style>body{display:none}</style><p>ok</p>');
        $this->assertStringNotContainsString('<style', $out);
        $this->assertStringNotContainsString('display:none', $out);
        $this->assertStringContainsString('ok', $out);
    }
}
