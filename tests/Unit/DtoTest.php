<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Data\BankDetailsInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Data\PaymentInput;
use AnjanTalukdar\GstInvoice\Data\RecipientInput;
use AnjanTalukdar\GstInvoice\Data\SupplierInput;
use AnjanTalukdar\GstInvoice\Enums\CodeType;
use AnjanTalukdar\GstInvoice\Enums\GstMode;
use AnjanTalukdar\GstInvoice\Enums\IndianState;
use AnjanTalukdar\GstInvoice\Enums\TaxCategory;
use AnjanTalukdar\GstInvoice\Tests\TestCase;

class DtoTest extends TestCase
{
    public function test_invoice_item_input_dto(): void
    {
        $item = InvoiceItemInput::make('Software License', 1000.0)
            ->quantity(2)
            ->unit('Nos')
            ->codeType(CodeType::SAC)
            ->code('998314')
            ->taxCategory(TaxCategory::TAXABLE)
            ->gstRate(18.0)
            ->discount(50.0);

        $this->assertEquals('Software License', $item->description);
        $this->assertEquals(1000.0, $item->unitPrice);
        $this->assertEquals(2.0, $item->quantity);
        $this->assertEquals('Nos', $item->unit);
        $this->assertEquals('SAC', $item->codeType);
        $this->assertEquals('998314', $item->code);
        $this->assertEquals('taxable', $item->taxCategory);
        $this->assertEquals(18.0, $item->gstRate);
        $this->assertEquals(50.0, $item->discount);

        $arrayData = $item->toArray();
        $this->assertEquals('Software License', $arrayData['description']);
        $this->assertEquals(1000.0, $arrayData['unit_price']);

        $restored = InvoiceItemInput::fromArray($arrayData);
        $this->assertEquals($item->description, $restored->description);
    }

    public function test_recipient_input_dto(): void
    {
        $recipient = RecipientInput::make('Acme Technologies', 'Acme Store')
            ->email('contact@acme.com')
            ->gstin('27AAACA123411ZS')
            ->stateCode(IndianState::MAHARASHTRA)
            ->shippingAddress('Express Towers')
            ->shippingCity('Mumbai')
            ->shippingStateCode(IndianState::MAHARASHTRA);

        $this->assertEquals('Acme Technologies', $recipient->name);
        $this->assertEquals('Acme Store', $recipient->tradeName);
        $this->assertEquals('27AAACA123411ZS', $recipient->gstin);
        $this->assertEquals('27', $recipient->stateCode);
        $this->assertEquals('Express Towers', $recipient->shippingAddress);
        $this->assertEquals('27', $recipient->shippingStateCode);
    }

    public function test_supplier_input_and_bank_details_dto(): void
    {
        $bank = BankDetailsInput::make('HDFC Bank', 'Software Corp', '50200012345678', 'HDFC0001234', 'Main Branch');
        $supplier = SupplierInput::make('Software Corp')
            ->gstin('18AABCL1234F1Z5')
            ->stateCode(IndianState::ASSAM)
            ->bankDetails($bank);

        $this->assertEquals('Software Corp', $supplier->name);
        $this->assertEquals('18AABCL1234F1Z5', $supplier->gstin);
        $this->assertEquals('18', $supplier->stateCode);
        $this->assertNotNull($supplier->bankDetails);
        $this->assertEquals('HDFC Bank', $supplier->bankDetails->bankName);
    }

    public function test_invoice_options_and_payment_input_dto(): void
    {
        $options = InvoiceOptions::make(GstMode::EXCLUSIVE)
            ->discount(100.0)
            ->isReverseCharge(false);

        $this->assertEquals('exclusive', $options->gstMode);
        $this->assertEquals(100.0, $options->discount);

        $payment = PaymentInput::make(500.0)
            ->referenceNumber('UTR123456');

        $this->assertEquals(500.0, $payment->amount);
        $this->assertEquals('UTR123456', $payment->referenceNumber);
    }
}
