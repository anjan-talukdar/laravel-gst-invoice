<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

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

    public function test_intra_state_gst_calculation_with_odd_paisa_split(): void
    {
        // Supplying 18% tax on item where tax is ₹100.11 (odd paisa)
        $items = [
            [
                'description' => 'SaaS Subscription',
                'code_type' => 'SAC',
                'code' => '998313',
                'quantity' => 1,
                'unit_price' => 556.1666,
                'gst_rate' => 18,
            ]
        ];

        $options = [
            'supplier_state_code' => '18', // Assam
            'pos_state_code' => '18',      // Assam (Intra-state)
            'odd_paisa_weightage' => 'cgst',
            'gst_mode' => 'exclusive',
        ];

        $summary = $this->calculator->calculate($items, $options);

        $this->assertFalse($summary->isInterstate);
        $this->assertEquals(100.11, $summary->summary->gstAmount);
        $this->assertEquals(50.06, $summary->summary->cgstAmount);
        $this->assertEquals(50.05, $summary->summary->sgstAmount);
        $this->assertEquals(0.00, $summary->summary->igstAmount);
        $this->assertEquals(100.11, round($summary->summary->cgstAmount + $summary->summary->sgstAmount, 2));

        // Test with odd_paisa_weightage = 'sgst'
        $options['odd_paisa_weightage'] = 'sgst';
        $summarySgst = $this->calculator->calculate($items, $options);
        $this->assertEquals(50.05, $summarySgst->summary->cgstAmount);
        $this->assertEquals(50.06, $summarySgst->summary->sgstAmount);
    }

    public function test_interstate_gst_calculation_igst(): void
    {
        $items = [
            [
                'description' => 'Medical Equipment',
                'code_type' => 'HSN',
                'code' => '9018',
                'quantity' => 2,
                'unit_price' => 1000.00,
                'gst_rate' => 12,
            ]
        ];

        $options = [
            'supplier_state_code' => '18', // Assam
            'pos_state_code' => '27',      // Maharashtra (Interstate)
            'gst_mode' => 'exclusive',
        ];

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
            [
                'description' => 'Exempt Good',
                'code_type' => 'HSN',
                'code' => '0101',
                'tax_category' => 'exempt',
                'quantity' => 1,
                'unit_price' => 500.00,
                'gst_rate' => 18,
            ]
        ];

        $summary = $this->calculator->calculate($items);

        $this->assertEquals(0.00, $summary->summary->gstAmount);
        $this->assertEquals(500.00, $summary->summary->total);
    }

    public function test_proportional_bill_discount_allocation(): void
    {
        $items = [
            [
                'description' => 'Item 1',
                'unit_price' => 600.00,
                'quantity' => 1,
                'gst_rate' => 18,
            ],
            [
                'description' => 'Item 2',
                'unit_price' => 400.00,
                'quantity' => 1,
                'gst_rate' => 18,
            ]
        ];

        $options = [
            'discount' => 100.00, // Bill discount
            'gst_mode' => 'exclusive',
        ];

        $summary = $this->calculator->calculate($items, $options);

        // 60% ratio to Item 1 (60), 40% ratio to Item 2 (40)
        $this->assertEquals(60.00, $summary->items[0]->billDiscount);
        $this->assertEquals(40.00, $summary->items[1]->billDiscount);
        $this->assertEquals(540.00, $summary->items[0]->taxableAmount);
        $this->assertEquals(360.00, $summary->items[1]->taxableAmount);
        $this->assertEquals(900.00, $summary->summary->subtotal);
    }
}
