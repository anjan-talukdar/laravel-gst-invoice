<?php

namespace AnjanTalukdar\GstInvoice\Contracts;

interface InvoicableInterface
{
    public function getInvoiceDescription(): string;
    public function getInvoiceUnitPrice(): float;
    public function getInvoiceGstRate(): float;
    public function getInvoiceCodeType(): string; // HSN or SAC
    public function getInvoiceCode(): string;     // Code string
}
