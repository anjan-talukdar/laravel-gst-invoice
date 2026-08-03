<?php

namespace AnjanTalukdar\GstInvoice\Events;

use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCancelling
{
    use Dispatchable, SerializesModels;

    public function __construct(public GstInvoice $invoice, public ?string $reason = null, public mixed $cancelledBy = null) {}
}
