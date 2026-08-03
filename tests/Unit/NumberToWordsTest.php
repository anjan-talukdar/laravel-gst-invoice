<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Helpers\NumberToWords;
use AnjanTalukdar\GstInvoice\Tests\TestCase;
use InvalidArgumentException;

class NumberToWordsTest extends TestCase
{
    public function test_converts_rupees_to_words(): void
    {
        $this->assertEquals('Rupees Zero Only', NumberToWords::toWords(0));
        $this->assertEquals('Rupees One Hundred Fifty Only', NumberToWords::toWords(150));
        $this->assertEquals('Rupees One Thousand Two Hundred Thirty Four and Fifty Paise Only', NumberToWords::toWords(1234.50));
        $this->assertEquals('Rupees One Lakh Twenty Five Thousand Four Hundred Fifty Only', NumberToWords::toWords(125450));
        $this->assertEquals('Rupees One Crore Two Lakh Three Thousand Four Hundred Fifty Six and Seventy Eight Paise Only', NumberToWords::toWords(10203456.78));
    }

    public function test_throws_exception_for_non_inr_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NumberToWords::toWords(100, 'USD');
    }
}
