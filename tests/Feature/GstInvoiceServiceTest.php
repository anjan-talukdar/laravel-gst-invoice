<?php

namespace AnjanTalukdar\GstInvoice\Tests\Feature;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\PaymentStatus;
use AnjanTalukdar\GstInvoice\Exceptions\InvoiceImmutableException;
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Models\GstInvoice as GstInvoiceModel;
use AnjanTalukdar\GstInvoice\Tests\TestCase;

class GstInvoiceServiceTest extends TestCase
{
    public function test_calculate_summary_without_saving_database(): void
    {
        $items = [
            ['description' => 'Web Hosting', 'unit_price' => 1000, 'gst_rate' => 18]
        ];

        $summary = GstInvoice::calculateSummary($items, ['gst_mode' => 'exclusive']);

        $this->assertEquals(1000.00, $summary->summary->subtotal);
        $this->assertEquals(180.00, $summary->summary->gstAmount);
        $this->assertEquals(1180.00, $summary->summary->total);
        $this->assertEquals(0, GstInvoiceModel::count());
    }

    public function test_creates_invoice_with_normalized_items_and_json_snapshot(): void
    {
        $recipient = [
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'gstin' => '18AABCL1234F1Z5',
            'address' => 'GS Road',
            'city' => 'Guwahati',
            'state_name' => 'Assam',
            'state_code' => '18',
        ];

        $items = [
            [
                'description' => 'SaaS Subscription - Pro Plan',
                'code_type' => 'SAC',
                'code' => '998313',
                'quantity' => 1,
                'unit_price' => 5000.00,
                'gst_rate' => 18,
            ]
        ];

        $options = [
            'supplier_state_code' => '18',
            'pos_state_code' => '18',
            'gst_mode' => 'exclusive',
            'payment_terms' => 'net_30',
        ];

        $invoice = GstInvoice::createInvoice($recipient, $items, $options);

        $this->assertInstanceOf(GstInvoiceModel::class, $invoice);
        $this->assertEquals(1, GstInvoiceModel::count());
        $this->assertEquals(1, $invoice->items()->count());

        $itemModel = $invoice->items->first();
        $this->assertEquals('SaaS Subscription - Pro Plan', $itemModel->description);
        $this->assertEquals('998313', $itemModel->code);
        $this->assertEquals(5000.00, $itemModel->unit_price);
        $this->assertEquals(900.00, $itemModel->total_amount - $itemModel->taxable_amount);

        // Verify cached JSON snapshot
        $snapshot = $invoice->billing_details;
        $this->assertEquals('1.0', $snapshot['schema_version']);
        $this->assertEquals('Acme Corp', $snapshot['recipient']['name']);
        $this->assertEquals(5900.00, $snapshot['summary']['total']);
    }

    public function test_enforces_invoice_immutability(): void
    {
        $invoice = GstInvoice::createInvoice(['name' => 'Test User'], [
            ['description' => 'Product A', 'unit_price' => 100]
        ]);

        $this->expectException(InvoiceImmutableException::class);

        // Trying to edit financial amounts on active invoice throws exception
        $invoice->update(['subtotal' => 999.00]);
    }

    public function test_mark_as_paid_and_cancellation(): void
    {
        $invoice = GstInvoice::createInvoice(['name' => 'Customer B'], [
            ['description' => 'Service B', 'unit_price' => 200]
        ]);

        $this->assertEquals(PaymentStatus::UNPAID, $invoice->payment_status);

        // Mark as paid
        GstInvoice::markAsPaid($invoice);
        $this->assertEquals(PaymentStatus::PAID, $invoice->payment_status);
        $this->assertEquals(0.00, $invoice->due_amount);

        // Cancel invoice
        GstInvoice::cancelInvoice($invoice, 'Entered by mistake', 'admin_1');
        $this->assertEquals(InvoiceStatus::CANCELLED, $invoice->status);
        $this->assertEquals('Entered by mistake', $invoice->cancellation_reason);
        $this->assertEquals('admin_1', $invoice->cancelled_by);
        $this->assertNotNull($invoice->cancelled_at);
    }
}
