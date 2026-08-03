<?php

namespace AnjanTalukdar\GstInvoice\Tests\Feature;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Events\InvoiceCancelled;
use AnjanTalukdar\GstInvoice\Events\InvoiceCancelling;
use AnjanTalukdar\GstInvoice\Events\InvoiceCreated;
use AnjanTalukdar\GstInvoice\Events\InvoiceCreating;
use AnjanTalukdar\GstInvoice\Events\InvoicePaid;
use AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanged;
use AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanging;
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class EventDispatcherTest extends TestCase
{
    public function test_dispatches_creating_and_created_events(): void
    {
        Event::fake();

        $invoice = GstInvoice::createInvoice(['name' => 'Jane Doe'], [
            ['description' => 'Consultation', 'unit_price' => 1000]
        ]);

        Event::assertDispatched(InvoiceCreating::class);
        Event::assertDispatched(InvoiceCreated::class, fn(InvoiceCreated $e) => $e->invoice->id === $invoice->id);
    }

    public function test_dispatches_payment_and_cancellation_events(): void
    {
        Event::fake();

        $invoice = GstInvoice::createInvoice(['name' => 'Jane Doe'], [
            ['description' => 'Consultation', 'unit_price' => 1000]
        ]);

        GstInvoice::markAsPaid($invoice);

        Event::assertDispatched(InvoicePaymentStatusChanging::class);
        Event::assertDispatched(InvoicePaymentStatusChanged::class);
        Event::assertDispatched(InvoicePaid::class);

        GstInvoice::cancelInvoice($invoice, 'Order cancelled by customer');

        Event::assertDispatched(InvoiceCancelling::class);
        Event::assertDispatched(InvoiceCancelled::class, fn(InvoiceCancelled $e) => $e->reason === 'Order cancelled by customer');
    }
}
