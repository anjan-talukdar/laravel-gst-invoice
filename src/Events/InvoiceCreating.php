<?php

namespace AnjanTalukdar\GstInvoice\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreating
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $data, public array $options = []) {}
}
