<?php

namespace Tests\Unit;

use App\Models\StatusCheat;
use PHPUnit\Framework\TestCase;

class StatusCheatTest extends TestCase
{
    public function test_known_status_codes_resolve_to_labels(): void
    {
        $this->assertSame('Рекомендуем к использованию', StatusCheat::statusLabel(1));
        $this->assertSame('Не рекомендуем к использованию', StatusCheat::statusLabel(2));
        $this->assertSame('На обновлении', StatusCheat::statusLabel(3));
        $this->assertSame('На свой страх и риск', StatusCheat::statusLabel(4));
    }

    public function test_unknown_status_falls_back_to_default(): void
    {
        $this->assertSame('Не определён', StatusCheat::statusLabel(0));
        $this->assertSame('Не определён', StatusCheat::statusLabel(999));
        $this->assertSame('Не определён', StatusCheat::statusLabel(-1));
    }

    public function test_statuses_constant_covers_all_codes(): void
    {
        $this->assertCount(4, StatusCheat::STATUSES);
        $this->assertSame([1, 2, 3, 4], array_keys(StatusCheat::STATUSES));
    }
}
