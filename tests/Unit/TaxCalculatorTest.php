<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Enums\CodeType;
use AnjanTalukdar\GstInvoice\Enums\GstMode;
use AnjanTalukdar\GstInvoice\Enums\OddPaisaWeightage;
use AnjanTalukdar\GstInvoice\Enums\TaxCategory;
use AnjanTalukdar\GstInvoice\Services\TaxCalculator;
use AnjanTalukdar\GstInvoice\Tests\TestCase;

class TaxCalculatorTest extends TestCase
{
    protected TaxCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TaxCalculator();
    }

    public function test_inclusive_gst_calculation_exact_line_total(): void
    {
        // ₹2299 plan inclusive of 18% GST
        $items1 = [
            InvoiceItemInput::make('Basic Lab', 2299.0)->quantity(1)->gstRate(18.0)
        ];
        $options1 = InvoiceOptions::make(GstMode::INCLUSIVE);
        $summary1 = $this->calculator->calculate($items1, $options1);

        $this->assertEquals(1948.31, $summary1->summary->subtotal);
        $this->assertEquals(350.69, $summary1->summary->gstAmount);
        $this->assertEquals(2299.00, $summary1->summary->total);

        // ₹4999 plan inclusive of 18% GST
        $items2 = [
            InvoiceItemInput::make('Advanced Diagnostics', 4999.0)->quantity(1)->gstRate(18.0)
        ];
        $options2 = InvoiceOptions::make(GstMode::INCLUSIVE);
        $summary2 = $this->calculator->calculate($items2, $options2);

        $this->assertEquals(4236.44, $summary2->summary->subtotal);
        $this->assertEquals(762.56, $summary2->summary->gstAmount);
        $this->assertEquals(4999.00, $summary2->summary->total);
    }

    public function test_intra_state_gst_calculation_with_odd_paisa_split(): void
    {
        $items = [
            InvoiceItemInput::make('SaaS Subscription', 556.1666)
                ->codeType(CodeType::SAC)
                ->code('998313')
                ->quantity(1)
                ->gstRate(18.0)
        ];

        $options = InvoiceOptions::make(GstMode::EXCLUSIVE)
            ->supplierStateCode('18')
            ->posStateCode('18')
            ->oddPaisaWeightage(OddPaisaWeightage::CGST);

        $summary = $this->calculator->calculate($items, $options);

        $this->assertFalse($summary->isInterstate);
        $this->assertEquals(100.11, $summary->summary->gstAmount);
        $this->assertEquals(50.06, $summary->summary->cgstAmount);
        $this->assertEquals(50.05, $summary->summary->sgstAmount);
        $this->assertEquals(0.00, $summary->summary->igstAmount);
        $this->assertEquals(100.11, round($summary->summary->cgstAmount + $summary->summary->sgstAmount, 2));

        // Test with odd_paisa_weightage = 'sgst'
        $options->oddPaisaWeightage(OddPaisaWeightage::SGST);
        $summarySgst = $this->calculator->calculate($items, $options);
        $this->assertEquals(50.05, $summarySgst->summary->cgstAmount);
        $this->assertEquals(50.06, $summarySgst->summary->sgstAmount);
    }

    public function test_interstate_gst_calculation_igst(): void
    {
        $items = [
            InvoiceItemInput::make('Medical Equipment', 1000.0)
                ->codeType(CodeType::HSN)
                ->code('9018')
                ->quantity(2)
                ->gstRate(12.0)
        ];

        $options = InvoiceOptions::make(GstMode::EXCLUSIVE)
            ->supplierStateCode('18')
            ->posStateCode('27');

        $summary = $this->calculator->calculate($items, $options);

        $this->assertTrue($summary->isInterstate);
        $this->assertEquals(2000.00, $summary->summary->subtotal);
        $this->assertEquals(0.00, $summary->summary->cgstAmount);
        $this->assertEquals(0.00, $summary->summary->sgstAmount);
        $this->assertEquals(240.00, $summary->summary->igstAmount);
        $this->assertEquals(2240.00, $summary->summary->total);
    }

    public function test_exempt_and_nil_rated_tax_categories(): void
    {
        $items = [
            InvoiceItemInput::make('Exempt Good', 500.0)
                ->codeType(CodeType::HSN)
                ->code('0101')
                ->taxCategory(TaxCategory::EXEMPT)
                ->quantity(1)
                ->gstRate(18.0)
        ];

        $summary = $this->calculator->calculate($items);

        $this->assertEquals(0.00, $summary->summary->gstAmount);
        $this->assertEquals(500.00, $summary->summary->total);
    }

    public function test_proportional_bill_discount_allocation(): void
    {
        $items = [
            InvoiceItemInput::make('Item 1', 600.0)->quantity(1)->gstRate(18.0),
            InvoiceItemInput::make('Item 2', 400.0)->quantity(1)->gstRate(18.0),
        ];

        $options = InvoiceOptions::make(GstMode::EXCLUSIVE)->discount(100.0);

        $summary = $this->calculator->calculate($items, $options);

        // 60% ratio to Item 1 (60), 40% ratio to Item 2 (40)
        $this->assertEquals(60.00, $summary->items[0]->billDiscount);
        $this->assertEquals(40.00, $summary->items[1]->billDiscount);
        $this->assertEquals(540.00, $summary->items[0]->taxableAmount);
        $this->assertEquals(360.00, $summary->items[1]->taxableAmount);
        $this->assertEquals(900.00, $summary->summary->subtotal);
    }
}
