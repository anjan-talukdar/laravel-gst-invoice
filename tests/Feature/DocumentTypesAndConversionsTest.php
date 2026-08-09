<?php

namespace AnjanTalukdar\GstInvoice\Tests\Feature;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Data\RecipientInput;
use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstInvoiceException;
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Models\InvoiceNumberSequence;
use AnjanTalukdar\GstInvoice\Tests\TestCase;
use Carbon\Carbon;

class DocumentTypesAndConversionsTest extends TestCase
{
    public function test_independent_number_sequences_for_all_document_types(): void
    {
        $recipient = RecipientInput::make('Test Recipient', gstin: '18AABCL1234F1Z5');
        $items = [InvoiceItemInput::make('Consulting Service', 1000.0, 1.0, gstRate: 18.0)];
        $date = Carbon::create(2026, 5, 10); // FY 26-27

        $quotation = GstInvoice::createQuotation($recipient, $items, InvoiceOptions::make(invoiceDate: $date));
        $taxInvoice = GstInvoice::createTaxInvoice($recipient, $items, InvoiceOptions::make(invoiceDate: $date));
        $receiptVoucher = GstInvoice::createReceiptVoucher($recipient, $items, InvoiceOptions::make(invoiceDate: $date));

        $this->assertEquals('QT/26-27/00001', $quotation->invoice_number);
        $this->assertEquals('INV/26-27/00001', $taxInvoice->invoice_number);
        $this->assertEquals('RV/26-27/00001', $receiptVoucher->invoice_number);

        $cnItem = [
            InvoiceItemInput::make('Return Service', 500.0, 1.0, gstRate: 18.0, referenceInvoiceItemId: $taxInvoice->items->first()->id)
        ];
        $creditNote = GstInvoice::createCreditNote($taxInvoice, $cnItem, InvoiceOptions::make(invoiceDate: $date));
        $this->assertEquals('CN/26-27/00001', $creditNote->invoice_number);

        $debitNote = GstInvoice::createDebitNote($taxInvoice, $items, InvoiceOptions::make(invoiceDate: $date));
        $this->assertEquals('DN/26-27/00001', $debitNote->invoice_number);
    }

    public function test_quotation_conversion_to_tax_invoice(): void
    {
        $recipient = RecipientInput::make('Customer Alpha');
        $items = [InvoiceItemInput::make('Development Work', 5000.0, 2.0, gstRate: 18.0)];

        $quotation = GstInvoice::createQuotation($recipient, $items);
        $this->assertEquals(InvoiceType::QUOTATION, $quotation->invoice_type);
        $this->assertNull($quotation->payment_status);

        // Cannot convert unaccepted quotation
        $this->expectException(InvalidGstInvoiceException::class);
        GstInvoice::convertQuotationToTaxInvoice($quotation);
    }

    public function test_accepted_quotation_converts_to_tax_invoice(): void
    {
        $recipient = RecipientInput::make('Customer Beta');
        $items = [InvoiceItemInput::make('Server Setup', 8000.0, 1.0, gstRate: 18.0)];

        $quotation = GstInvoice::createQuotation($recipient, $items);
        $quotation->update(['status' => InvoiceStatus::ACCEPTED->value]);

        $taxInvoice = GstInvoice::convertQuotationToTaxInvoice($quotation);

        $this->assertEquals(InvoiceType::TAX_INVOICE, $taxInvoice->invoice_type);
        $this->assertEquals($quotation->id, $taxInvoice->reference_invoice_id);
        $this->assertEquals(1220.34, (float)$taxInvoice->gst_amount);
        $this->assertEquals(8000.00, (float)$taxInvoice->total);
        $this->assertEquals('unpaid', $taxInvoice->payment_status->value);

        // Original quotation remains unmodified as accepted
        $quotation->refresh();
        $this->assertEquals(InvoiceStatus::ACCEPTED, $quotation->status);
    }

    public function test_create_revised_quotation(): void
    {
        $recipient = RecipientInput::make('Customer Gamma');
        $items1 = [InvoiceItemInput::make('Phase 1', 1000.0)];

        $quotation1 = GstInvoice::createQuotation($recipient, $items1);

        $items2 = [InvoiceItemInput::make('Phase 1 Revised', 1200.0)];
        $revisedQuotation = GstInvoice::createRevisedQuotation($quotation1, $items2);

        $this->assertEquals(InvoiceType::QUOTATION, $revisedQuotation->invoice_type);
        $this->assertEquals($quotation1->id, $revisedQuotation->reference_invoice_id);
        $this->assertStringStartsWith('QT/', $revisedQuotation->invoice_number);
    }

    public function test_credit_note_line_item_referencing(): void
    {
        $recipient = RecipientInput::make('Customer Delta');
        $origItems = [
            InvoiceItemInput::make('Item A', 1000.0, 2.0, gstRate: 18.0),
            InvoiceItemInput::make('Item B', 500.0, 1.0, gstRate: 18.0),
        ];

        $taxInvoice = GstInvoice::createTaxInvoice($recipient, $origItems);
        $itemA = $taxInvoice->items->where('description', 'Item A')->first();

        $cnItems = [
            InvoiceItemInput::make('Return 1 Item A', 1000.0, 1.0, gstRate: 18.0, referenceInvoiceItemId: $itemA->id),
        ];

        $creditNote = GstInvoice::createCreditNote($taxInvoice, $cnItems);

        $this->assertEquals(InvoiceType::CREDIT_NOTE, $creditNote->invoice_type);
        $this->assertEquals($taxInvoice->id, $creditNote->reference_invoice_id);
        $this->assertEquals($itemA->id, $creditNote->items->first()->reference_invoice_item_id);
        $this->assertNull($creditNote->payment_status);
    }

    public function test_artisan_sync_sequences_command(): void
    {
        config(['gst-invoice.prefixes.tax_invoice' => 'INV-TEST']);

        $this->artisan('gst-invoice:sync sequences')
            ->assertExitCode(0);

        $sequence = InvoiceNumberSequence::where('invoice_type', 'tax_invoice')->first();
        $this->assertNotNull($sequence);
        $this->assertEquals('INV-TEST', $sequence->prefix);
    }
}
