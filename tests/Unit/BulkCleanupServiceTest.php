<?php

namespace Tests\Unit;

use App\Services\BulkCleanupService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BulkCleanupServiceTest extends TestCase
{
    private BulkCleanupService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new BulkCleanupService();
    }

    public function test_rejects_negative_or_zero_limit_for_orders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->cleanupOrders(BulkCleanupService::ORDER_TYPE_EXPIRED, 0);
    }

    public function test_rejects_oversized_limit_for_orders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->cleanupOrders(BulkCleanupService::ORDER_TYPE_EXPIRED, BulkCleanupService::MAX_PER_CALL + 1);
    }

    public function test_rejects_unknown_order_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->cleanupOrders('refunded', 10);
    }

    public function test_rejects_unknown_file_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->cleanupFiles('thumbs', 10);
    }

    public function test_rejects_negative_limit_for_coupons(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->cleanupCoupons(-5);
    }

    public function test_valid_order_types_constants(): void
    {
        $this->assertContains('expired', BulkCleanupService::VALID_ORDER_TYPES);
        $this->assertContains('paid',    BulkCleanupService::VALID_ORDER_TYPES);
        $this->assertCount(2, BulkCleanupService::VALID_ORDER_TYPES);
    }

    public function test_valid_file_types_constants(): void
    {
        $this->assertContains('covers',  BulkCleanupService::VALID_FILE_TYPES);
        $this->assertContains('avatars', BulkCleanupService::VALID_FILE_TYPES);
        $this->assertContains('files',   BulkCleanupService::VALID_FILE_TYPES);
        $this->assertCount(3, BulkCleanupService::VALID_FILE_TYPES);
    }

    public function test_max_per_call_is_sane(): void
    {
        $this->assertGreaterThan(0, BulkCleanupService::MAX_PER_CALL);
        $this->assertLessThanOrEqual(50000, BulkCleanupService::MAX_PER_CALL);
    }
}
