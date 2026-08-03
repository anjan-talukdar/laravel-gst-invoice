<?php

namespace AnjanTalukdar\GstInvoice\Contracts;

use DateTimeInterface;

interface InvoiceNumberGeneratorInterface
{
    public function generate(DateTimeInterface $date, array $options = []): string;
}
