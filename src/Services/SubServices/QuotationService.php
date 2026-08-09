<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Events\QuotationAccepted;
use AnjanTalukdar\GstInvoice\Events\QuotationConvertedToInvoice;
use AnjanTalukdar\GstInvoice\Events\QuotationExpired;
use AnjanTalukdar\GstInvoice\Events\QuotationRejected;
use AnjanTalukdar\GstInvoice\Events\QuotationSent;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstInvoiceException;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceService;
use Illuminate\Database\Eloquent\Collection;

class QuotationService
{
    public function __construct(protected GstInvoiceService $service) {}

    public function create(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        return $this->service->createQuotation($recipient, $items, $options);
    }

    public function send(GstInvoice $quotation): GstInvoice
    {
        $this->ensureQuotation($quotation);
        $quotation->update(['status' => InvoiceStatus::SENT->value]);
        event(new QuotationSent($quotation));
        return $quotation;
    }

    public function accept(GstInvoice $quotation): GstInvoice
    {
        $this->ensureQuotation($quotation);
        $quotation->update(['status' => InvoiceStatus::ACCEPTED->value]);
        event(new QuotationAccepted($quotation));
        return $quotation;
    }

    public function reject(GstInvoice $quotation, ?string $reason = null): GstInvoice
    {
        $this->ensureQuotation($quotation);
        $quotation->update([
            'status' => InvoiceStatus::REJECTED->value,
            'remark' => $reason ? "Rejected: {$reason}" : $quotation->remark,
        ]);
        event(new QuotationRejected($quotation, $reason));
        return $quotation;
    }

    public function expire(GstInvoice $quotation): GstInvoice
    {
        $this->ensureQuotation($quotation);
        $quotation->update(['status' => InvoiceStatus::EXPIRED->value]);
        event(new QuotationExpired($quotation));
        return $quotation;
    }

    public function cancel(GstInvoice $quotation, ?string $reason = null, mixed $cancelledBy = null): bool
    {
        $this->ensureQuotation($quotation);
        return $this->service->cancelInvoice($quotation, $reason, $cancelledBy);
    }

    public function createRevised(GstInvoice $quotation, ?array $items = null, ?InvoiceOptions $options = null): GstInvoice
    {
        return $this->service->createRevisedQuotation($quotation, $items, $options);
    }

    public function convertToInvoice(GstInvoice $quotation, ?array $items = null, ?InvoiceOptions $options = null): GstInvoice
    {
        $invoice = $this->service->convertQuotationToTaxInvoice($quotation, $items, $options);
        event(new QuotationConvertedToInvoice($quotation, $invoice));
        return $invoice;
    }

    public function duplicate(GstInvoice $quotation): GstInvoice
    {
        $this->ensureQuotation($quotation);
        $recipient = $quotation->recipient ?: $quotation->recipient_name;
        $items = $quotation->items->toArray();

        return $this->create($recipient, $items);
    }

    public function getRevisions(GstInvoice $quotation): Collection
    {
        $this->ensureQuotation($quotation);
        return GstInvoice::where('reference_invoice_id', $quotation->id)
            ->where('invoice_type', InvoiceType::QUOTATION->value)
            ->get();
    }

    public function getConversions(GstInvoice $quotation): Collection
    {
        $this->ensureQuotation($quotation);
        return GstInvoice::where('reference_invoice_id', $quotation->id)
            ->where('invoice_type', InvoiceType::TAX_INVOICE->value)
            ->get();
    }

    protected function ensureQuotation(GstInvoice $invoice): void
    {
        if ($invoice->invoice_type !== InvoiceType::QUOTATION) {
            throw new InvalidGstInvoiceException("Document is not a quotation.");
        }
    }
}
