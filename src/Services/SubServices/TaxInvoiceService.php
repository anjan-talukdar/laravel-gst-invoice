<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceService;
use Illuminate\Database\Eloquent\Collection;

class TaxInvoiceService
{
    public function __construct(protected GstInvoiceService $service) {}

    public function create(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        return $this->service->createTaxInvoice($recipient, $items, $options);
    }

    public function issue(GstInvoice $invoice): GstInvoice
    {
        return $this->service->issueDocument($invoice);
    }

    public function cancel(GstInvoice $invoice, ?string $reason = null, mixed $cancelledBy = null): bool
    {
        return $this->service->cancelInvoice($invoice, $reason, $cancelledBy);
    }

    public function validateBeforeIssue(GstInvoice $invoice): bool
    {
        return $this->service->validateDocumentBeforeIssue($invoice);
    }

    public function recalculate(GstInvoice $invoice): GstInvoice
    {
        return $this->service->recalculateDocument($invoice);
    }

    public function getOriginalDocument(GstInvoice $invoice): ?GstInvoice
    {
        return $invoice->referenceInvoice;
    }

    public function getRelatedDocuments(GstInvoice $invoice): Collection
    {
        return $invoice->childInvoices;
    }
}
