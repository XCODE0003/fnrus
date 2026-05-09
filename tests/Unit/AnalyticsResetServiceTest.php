<?php

namespace Tests\Unit;

use App\Services\AnalyticsResetService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AnalyticsResetServiceTest extends TestCase
{
    private AnalyticsResetService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new AnalyticsResetService();
    }

    public function test_rejects_unknown_scope(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->reset('orders');
    }

    public function test_valid_scopes_constants(): void
    {
        $expected = ['all', 'products', 'categories', 'coupons', 'senders'];
        $this->assertSame($expected, AnalyticsResetService::VALID_SCOPES);
    }

    public function test_scope_constants_match_strings(): void
    {
        $this->assertSame('all',        AnalyticsResetService::SCOPE_ALL);
        $this->assertSame('products',   AnalyticsResetService::SCOPE_PRODUCTS);
        $this->assertSame('categories', AnalyticsResetService::SCOPE_CATEGORIES);
        $this->assertSame('coupons',    AnalyticsResetService::SCOPE_COUPONS);
        $this->assertSame('senders',    AnalyticsResetService::SCOPE_SENDERS);
    }
}
