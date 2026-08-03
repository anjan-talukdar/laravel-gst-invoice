<?php

namespace AnjanTalukdar\GstInvoice\Events;

use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoicePartiallyPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public GstInvoice $invoice, public float $paidAmount, public float $dueAmount) {}
}
