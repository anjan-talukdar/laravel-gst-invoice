<?php

namespace AnjanTalukdar\GstInvoice\Exceptions;

use Exception;

class InvoiceImmutableException extends Exception
{
    public function __construct(string $invoiceNumber)
    {
        parent::__construct("Cannot modify financial details of finalized invoice '{$invoiceNumber}'. Invoices are immutable once created.");
    }
}
