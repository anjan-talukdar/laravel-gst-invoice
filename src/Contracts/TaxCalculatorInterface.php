<?php

namespace AnjanTalukdar\GstInvoice\Contracts;

use AnjanTalukdar\GstInvoice\Data\BillingSummaryData;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;

interface TaxCalculatorInterface
{
    /**
     * Calculate billing summary for invoice line items.
     *
     * @param InvoiceItemInput[] $items
     * @param InvoiceOptions|null $options
     */
    public function calculate(array $items, ?InvoiceOptions $options = null): BillingSummaryData;
}
