<?php

namespace AnjanTalukdar\GstInvoice\Tests\Feature;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Data\RecipientInput;
use AnjanTalukdar\GstInvoice\Data\SupplierInput;
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
        $billOfSupply = GstInvoice::billsOfSupply()->create($recipient, $items, InvoiceOptions::make(invoiceDate: $date));
        $simpleReceipt = GstInvoice::simpleReceipts()->create($recipient, $items, InvoiceOptions::make(invoiceDate: $date));

        $this->assertEquals('QT/26-27/00001', $quotation->invoice_number);
        $this->assertEquals('INV/26-27/00001', $taxInvoice->invoice_number);
        $this->assertEquals('RV/26-27/00001', $receiptVoucher->invoice_number);
        $this->assertEquals('BOS/26-27/00001', $billOfSupply->invoice_number);
        $this->assertEquals('REC/26-27/00001', $simpleReceipt->invoice_number);

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

        $quotation = GstInvoice::quotations()->create($recipient, $items);
        GstInvoice::quotations()->accept($quotation);

        $taxInvoice = GstInvoice::quotations()->convertToInvoice($quotation);

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

    public function test_credit_note_remaining_adjustable_amount(): void
    {
        $recipient = RecipientInput::make('Customer Delta', gstin: '18AABCL1234F1Z5');
        $origItems = [
            InvoiceItemInput::make('Item A', 1000.0, 2.0, gstRate: 18.0),
            InvoiceItemInput::make('Item B', 500.0, 1.0, gstRate: 18.0),
        ];

        $taxInvoice = GstInvoice::createTaxInvoice($recipient, $origItems);
        $itemA = $taxInvoice->items->where('description', 'Item A')->first();

        $this->assertEquals(2500.00, GstInvoice::creditNotes()->getRemainingAdjustableAmount($taxInvoice));

        $cnItems = [
            InvoiceItemInput::make('Return 1 Item A', 1000.0, 1.0, gstRate: 18.0, referenceInvoiceItemId: $itemA->id),
        ];

        $creditNote = GstInvoice::creditNotes()->create($taxInvoice, $cnItems);

        $this->assertEquals(InvoiceType::CREDIT_NOTE, $creditNote->invoice_type);
        $this->assertEquals($taxInvoice->id, $creditNote->reference_invoice_id);
        $this->assertEquals($itemA->id, $creditNote->items->first()->reference_invoice_item_id);

        $this->assertEquals(1500.00, GstInvoice::creditNotes()->getRemainingAdjustableAmount($taxInvoice));
        $this->assertEquals(1000.00, GstInvoice::creditNotes()->getRemainingAdjustableAmount($taxInvoice, $itemA->id));
    }

    public function test_compliance_service_gstr1_exports(): void
    {
        $recipient = RecipientInput::make('B2B Client', gstin: '27AAACA123411ZS', stateCode: '27');
        $items = [InvoiceItemInput::make('Dev Work', 10000.00, gstRate: 18.0)];

        $invoice = GstInvoice::createTaxInvoice($recipient, $items);

        $gstr1 = GstInvoice::reports()->getGstr1Data();
        $this->assertNotEmpty($gstr1['b2b']);
        $this->assertEquals('27AAACA123411ZS', $gstr1['b2b'][0]['gstin']);

        $json = GstInvoice::reports()->exportGstr1Json();
        $this->assertStringContainsString('27AAACA123411ZS', $json);

        $csv = GstInvoice::reports()->exportGstr1Csv();
        $this->assertStringContainsString('27AAACA123411ZS', $csv);
    }

    public function test_einvoice_payload_generation(): void
    {
        $recipient = RecipientInput::make('B2B Corporate', gstin: '27AAACA123411ZS', address: 'Main St', city: 'Mumbai', stateCode: '27', pincode: '400001');
        $items = [InvoiceItemInput::make('Enterprise Software', 50000.00, gstRate: 18.0)];

        $options = InvoiceOptions::make()
            ->supplier(SupplierInput::make(
                name: 'Supplier Corp',
                gstin: '18AABCL1234F1Z5',
                address: 'GS Road',
                city: 'Guwahati',
                stateCode: '18',
                pincode: '781005'
            ));

        $invoice = GstInvoice::createTaxInvoice($recipient, $items, $options);

        $this->assertTrue(GstInvoice::eInvoice()->isApplicable($invoice));
        $payload = GstInvoice::eInvoice()->generatePayload($invoice);

        $this->assertEquals('1.1', $payload['Version']);
        $this->assertEquals('27AAACA123411ZS', $payload['BuyerDtls']['Gstin']);
        $this->assertEquals('18AABCL1234F1Z5', $payload['SellerDtls']['Gstin']);
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
