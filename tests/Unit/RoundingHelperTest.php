<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Enums\RoundingStrategy;
use AnjanTalukdar\GstInvoice\Helpers\RoundingHelper;
use AnjanTalukdar\GstInvoice\Tests\TestCase;

class RoundingHelperTest extends TestCase
{
    public function test_standard_rounding(): void
    {
        $this->assertEquals(12.35, RoundingHelper::round(12.345, RoundingStrategy::STANDARD));
        $this->assertEquals(12.34, RoundingHelper::round(12.344, RoundingStrategy::STANDARD));
    }

    public function test_floor_and_ceil_rounding(): void
    {
        $this->assertEquals(12.34, RoundingHelper::round(12.349, RoundingStrategy::FLOOR));
        $this->assertEquals(12.35, RoundingHelper::round(12.341, RoundingStrategy::CEIL));
    }

    public function test_bankers_rounding(): void
    {
        $this->assertEquals(12.34, RoundingHelper::round(12.345, RoundingStrategy::BANKERS));
        $this->assertEquals(12.36, RoundingHelper::round(12.355, RoundingStrategy::BANKERS));
    }
}
