<?php

namespace AnjanTalukdar\GstInvoice\Events;

use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public GstInvoice $quotation) {}
}
