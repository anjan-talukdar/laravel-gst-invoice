<?php

namespace AnjanTalukdar\GstInvoice\Contracts;

use AnjanTalukdar\GstInvoice\Data\BillingSummaryData;

interface TaxCalculatorInterface
{
    public function calculate(array $items, array $options = []): BillingSummaryData;
}
