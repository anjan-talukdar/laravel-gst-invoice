<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceService;

class SimpleReceiptService
{
    public function __construct(protected GstInvoiceService $service) {}

    public function create(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        return $this->service->createSimpleReceipt($recipient, $items, $options);
    }

    public function issue(GstInvoice $receipt): GstInvoice
    {
        return $this->service->issueDocument($receipt);
    }

    public function cancel(GstInvoice $receipt, ?string $reason = null, mixed $cancelledBy = null): bool
    {
        return $this->service->cancelInvoice($receipt, $reason, $cancelledBy);
    }

    public function forceUpdate(
        GstInvoice $receipt,
        mixed $recipient = null,
        ?array $items = null,
        ?InvoiceOptions $options = null,
        array $additionalAttributes = []
    ): GstInvoice {
        return $this->service->forceUpdateInvoice($receipt, $recipient, $items, $options, $additionalAttributes);
    }
}
