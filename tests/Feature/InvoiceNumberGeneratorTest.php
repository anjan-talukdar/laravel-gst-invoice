<?php

namespace AnjanTalukdar\GstInvoice\Tests\Feature;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Contracts\InvoiceNumberGeneratorInterface;
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Services\SequentialFyInvoiceNumberGenerator;
use AnjanTalukdar\GstInvoice\Tests\TestCase;
use Carbon\Carbon;
use DateTimeInterface;

class InvoiceNumberGeneratorTest extends TestCase
{
    public function test_sequential_fy_invoice_number_generator(): void
    {
        $generator = new SequentialFyInvoiceNumberGenerator();
        $date = Carbon::create(2025, 5, 10); // FY 25-26

        $num1 = $generator->generate($date);
        $this->assertEquals('INV/25-26/00001', $num1);

        // Create an invoice with this number
        GstInvoice::createInvoice(['name' => 'John Doe'], [
            ['description' => 'Service', 'unit_price' => 100]
        ], ['invoice_date' => $date, 'invoice_number' => $num1]);

        $num2 = $generator->generate($date);
        $this->assertEquals('INV/25-26/00002', $num2);
    }

    public function test_custom_invoice_number_generator_swapping(): void
    {
        $customGenerator = new class implements InvoiceNumberGeneratorInterface {
            public function generate(DateTimeInterface $date, array $options = []): string
            {
                return 'CUSTOM-2026-999';
            }
        };

        $this->app->bind(InvoiceNumberGeneratorInterface::class, fn() => $customGenerator);

        $invoice = GstInvoice::createInvoice(['name' => 'Custom Customer'], [
            ['description' => 'Custom Item', 'unit_price' => 200]
        ]);

        $this->assertEquals('CUSTOM-2026-999', $invoice->invoice_number);
    }
}
