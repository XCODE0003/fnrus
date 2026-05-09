<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ForcePasswordResetService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ForcePasswordResetServiceTest extends TestCase
{
    private ForcePasswordResetService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ForcePasswordResetService();
    }

    public function test_token_ttl_is_sane(): void
    {
        $this->assertGreaterThanOrEqual(1, ForcePasswordResetService::TOKEN_TTL_HOURS);
        $this->assertLessThanOrEqual(168, ForcePasswordResetService::TOKEN_TTL_HOURS);
    }

    public function test_password_bounds_are_sane(): void
    {
        $this->assertGreaterThanOrEqual(8, ForcePasswordResetService::PASSWORD_MIN);
        $this->assertGreaterThanOrEqual(ForcePasswordResetService::PASSWORD_MIN, ForcePasswordResetService::PASSWORD_MAX);
        $this->assertLessThanOrEqual(256, ForcePasswordResetService::PASSWORD_MAX);
    }

    public function test_complete_rejects_short_token(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Недействительная/');
        $this->svc->complete('shorttoken', 'Strong#Pass1');
    }

    public function test_complete_rejects_long_token(): void
    {
        $this->expectException(RuntimeException::class);
        $this->svc->complete(str_repeat('a', 100), 'Strong#Pass1');
    }

    public function test_complete_rejects_short_password(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/символов/');
        $this->svc->complete(str_repeat('a', 64), '123');
    }

    public function test_complete_rejects_oversized_password(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/символов/');
        $this->svc->complete(str_repeat('a', 64), str_repeat('x', 200));
    }

    public function test_password_min_blocks_seven_chars(): void
    {
        $this->expectException(RuntimeException::class);
        $this->svc->complete(str_repeat('a', 64), '1234567');
    }
}
