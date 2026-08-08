<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstInvoiceException;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstinException;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceValidator;
use AnjanTalukdar\GstInvoice\Tests\TestCase;

class ValidatorTest extends TestCase
{
    protected GstInvoiceValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new GstInvoiceValidator();
    }

    public function test_gstin_validation_and_pan_extraction(): void
    {
        $validGstin = '18AABCL1234F1Z5';
        $this->assertTrue($this->validator->validateGstin($validGstin));
        $this->assertEquals('AABCL1234F', $this->validator->extractPanFromGstin($validGstin));
    }

    public function test_invalid_gstin_throws_exception(): void
    {
        $this->expectException(InvalidGstinException::class);
        $this->validator->validateGstin('INVALIDGSTIN');
    }

    public function test_invoice_input_validation_empty_items(): void
    {
        $this->expectException(InvalidGstInvoiceException::class);
        $this->validator->validateInvoiceInput([]);
    }

    public function test_invoice_input_validation_invalid_rate(): void
    {
        $items = [
            InvoiceItemInput::make('Test', 100.0)
                ->quantity(1)
                ->gstRate(47.0) // Invalid GST rate
        ];

        $this->expectException(InvalidGstInvoiceException::class);
        $this->validator->validateInvoiceInput($items);
    }
}
