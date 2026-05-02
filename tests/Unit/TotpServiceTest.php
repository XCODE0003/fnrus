<?php

namespace Tests\Unit;

use App\Services\TotpService;
use Tests\TestCase;

/**
 * Pure unit tests for TotpService — no DB, no HTTP.
 */
class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService();
    }

    public function test_generated_secret_is_base32(): void
    {
        $secret = $this->totp->generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertGreaterThanOrEqual(16, strlen($secret));
    }

    public function test_verify_accepts_current_code(): void
    {
        $secret = $this->totp->generateSecret();
        $now = (int) floor(time() / 30);
        $code = $this->totp->codeAt($secret, $now);
        $this->assertTrue($this->totp->verify($secret, $code));
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $secret = $this->totp->generateSecret();
        $this->assertFalse($this->totp->verify($secret, '000000'));
        $this->assertFalse($this->totp->verify($secret, ''));
        $this->assertFalse($this->totp->verify($secret, 'abcdef'));
    }

    public function test_verify_accepts_previous_window(): void
    {
        $secret = $this->totp->generateSecret();
        $prev = (int) floor(time() / 30) - 1;
        $code = $this->totp->codeAt($secret, $prev);
        $this->assertTrue($this->totp->verify($secret, $code, 1));
    }

    public function test_recovery_codes_format_and_count(): void
    {
        $codes = $this->totp->generateRecoveryCodes();
        $this->assertCount(10, $codes);
        foreach ($codes as $c) {
            $this->assertMatchesRegularExpression('/^[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}$/', $c);
        }
    }

    public function test_consume_recovery_code_removes_used(): void
    {
        $codes = $this->totp->generateRecoveryCodes();
        $hashes = $this->totp->hashRecoveryCodes($codes);

        $remaining = $this->totp->consumeRecoveryCode($codes[3], $hashes);
        $this->assertNotNull($remaining);
        $this->assertCount(9, $remaining);

        // Same code cannot be reused.
        $this->assertNull($this->totp->consumeRecoveryCode($codes[3], $remaining));
    }

    public function test_consume_recovery_code_rejects_unknown(): void
    {
        $codes = $this->totp->generateRecoveryCodes();
        $hashes = $this->totp->hashRecoveryCodes($codes);
        $this->assertNull($this->totp->consumeRecoveryCode('zzzz-zzzz-zzzz', $hashes));
    }

    public function test_provisioning_uri_contains_required_params(): void
    {
        $uri = $this->totp->provisioningUri('user@example.com', 'JBSWY3DPEHPK3PXP', 'Acme');
        $this->assertStringStartsWith('otpauth://totp/Acme:user%40example.com?', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=Acme', $uri);
        $this->assertStringContainsString('algorithm=SHA1', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }
}
