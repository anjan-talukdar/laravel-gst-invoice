<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Events\AdvanceReceived;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceService;

class ReceiptVoucherService
{
    public function __construct(protected GstInvoiceService $service) {}

    public function createAdvance(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $voucher = $this->service->createReceiptVoucher($recipient, $items, $options);
        event(new AdvanceReceived($voucher));
        return $voucher;
    }

    public function applyToInvoice(GstInvoice $receiptVoucher, GstInvoice $taxInvoice): GstInvoice
    {
        $receiptVoucher->update(['reference_invoice_id' => $taxInvoice->id]);
        return $receiptVoucher;
    }

    public function cancel(GstInvoice $voucher, ?string $reason = null, mixed $cancelledBy = null): bool
    {
        return $this->service->cancelInvoice($voucher, $reason, $cancelledBy);
    }
}
