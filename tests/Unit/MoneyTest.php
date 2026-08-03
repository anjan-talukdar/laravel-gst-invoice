<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Enums\RoundingStrategy;
use AnjanTalukdar\GstInvoice\Tests\TestCase;
use AnjanTalukdar\GstInvoice\ValueObjects\Money;

class MoneyTest extends TestCase
{
    public function test_money_creation_and_arithmetic(): void
    {
        $m1 = Money::of(100.50);
        $m2 = Money::of(50.25);

        $this->assertEquals(150.75, $m1->add($m2)->getAmount());
        $this->assertEquals(50.25, $m1->subtract($m2)->getAmount());
        $this->assertEquals(201.00, $m1->multiply(2)->getAmount());
        $this->assertEquals(18.09, $m1->percentage(18)->getAmount());
    }

    public function test_money_rounding_strategies(): void
    {
        $m = Money::of(100.126);

        $this->assertEquals(100.13, $m->round(RoundingStrategy::STANDARD)->getAmount());
        $this->assertEquals(100.12, $m->round(RoundingStrategy::FLOOR)->getAmount());
        $this->assertEquals(100.13, $m->round(RoundingStrategy::CEIL)->getAmount());
    }

    public function test_money_comparisons_and_formatting(): void
    {
        $m1 = Money::of(100.00);
        $m2 = Money::of(100.00);
        $m3 = Money::of(50.00);

        $this->assertTrue($m1->equals($m2));
        $this->assertTrue($m1->greaterThan($m3));
        $this->assertEquals('₹100.00', $m1->formatted());
    }
}
