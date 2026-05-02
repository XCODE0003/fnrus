<?php

namespace Tests\Unit;

use App\Http\Middleware\BlockLegacyAdminPath;
use App\Http\Middleware\IPWhitelist;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Pure middleware tests — no full app boot, no DB.
 *
 * IPWhitelist is exercised indirectly: we set config('admin.allowed_ips')
 * and assert the middleware passes/blocks based on the request IP.
 */
class IPWhitelistTest extends TestCase
{
    public function test_empty_allowlist_allows_any_ip(): void
    {
        config()->set('admin.allowed_ips', []);
        $mw = new IPWhitelist();
        $req = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '1.2.3.4']);
        $res = $mw->handle($req, fn () => new Response('ok'));
        $this->assertSame('ok', $res->getContent());
    }

    public function test_exact_ip_is_allowed(): void
    {
        config()->set('admin.allowed_ips', ['203.0.113.7']);
        $mw = new IPWhitelist();
        $req = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.7']);
        $res = $mw->handle($req, fn () => new Response('ok'));
        $this->assertSame('ok', $res->getContent());
    }

    public function test_cidr_block_is_allowed(): void
    {
        config()->set('admin.allowed_ips', ['198.51.100.0/24']);
        $mw = new IPWhitelist();
        $req = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.51.100.42']);
        $res = $mw->handle($req, fn () => new Response('ok'));
        $this->assertSame('ok', $res->getContent());
    }

    public function test_outside_cidr_is_denied(): void
    {
        config()->set('admin.allowed_ips', ['198.51.100.0/24']);
        $mw = new IPWhitelist();
        $req = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.51.101.1']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $mw->handle($req, fn () => new Response('ok'));
    }

    public function test_block_legacy_admin_path_404s_known_paths(): void
    {
        $mw = new BlockLegacyAdminPath();
        foreach (['wp-admin', 'wp-login', 'administrator', 'phpmyadmin', 'manager', 'admin.php'] as $p) {
            $req = Request::create('/' . $p);
            try {
                $mw->handle($req, fn () => new Response('ok'));
                $this->fail("expected 404 for /$p");
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
    }

    public function test_block_legacy_admin_passes_through_other_paths(): void
    {
        $mw = new BlockLegacyAdminPath();
        $req = Request::create('/some/regular/path');
        $res = $mw->handle($req, fn () => new Response('ok'));
        $this->assertSame('ok', $res->getContent());
    }

    public function test_security_headers_applied(): void
    {
        config()->set('admin.security_headers.csp_report_only', true);
        $mw = new SecurityHeaders();
        $req = Request::create('/');
        $res = $mw->handle($req, fn () => new Response('ok'));
        $this->assertSame('DENY', $res->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $res->headers->get('Referrer-Policy'));
        $this->assertNotEmpty($res->headers->get('Permissions-Policy'));
        $this->assertNotEmpty($res->headers->get('Content-Security-Policy-Report-Only'));
        // No CSP-enforce header in report-only mode.
        $this->assertNull($res->headers->get('Content-Security-Policy'));
    }

    public function test_security_headers_enforce_mode(): void
    {
        config()->set('admin.security_headers.csp_report_only', false);
        $mw = new SecurityHeaders();
        $req = Request::create('/');
        $res = $mw->handle($req, fn () => new Response('ok'));
        $this->assertNotEmpty($res->headers->get('Content-Security-Policy'));
    }
}
