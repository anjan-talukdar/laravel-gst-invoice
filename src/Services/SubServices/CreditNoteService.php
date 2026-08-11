<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstInvoiceException;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Models\GstInvoiceItem;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceService;

class CreditNoteService
{
    public function __construct(protected GstInvoiceService $service) {}

    public function create(GstInvoice $originalInvoice, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $this->validateAdjustableLimit($originalInvoice, $items);
        return $this->service->createCreditNote($originalInvoice, $items, $options);
    }

    public function createFromInvoice(GstInvoice $originalInvoice, ?InvoiceOptions $options = null): GstInvoice
    {
        $items = $originalInvoice->items->map(function (GstInvoiceItem $item) {
            return [
                'description' => $item->description,
                'unit_price' => (float)$item->unit_price,
                'quantity' => (float)$item->quantity,
                'unit' => $item->unit,
                'code_type' => $item->code_type?->value ?? 'SAC',
                'code' => $item->code,
                'tax_category' => $item->tax_category?->value ?? 'taxable',
                'gst_rate' => (float)$item->gst_rate,
                'discount' => (float)$item->item_discount,
                'reference_invoice_item_id' => $item->id,
            ];
        })->toArray();

        return $this->create($originalInvoice, $items, $options);
    }

    public function getRemainingAdjustableAmount(GstInvoice $originalInvoice, ?int $itemId = null): float
    {
        if ($itemId !== null) {
            $item = GstInvoiceItem::where('gst_invoice_id', $originalInvoice->id)->where('id', $itemId)->first();
            if (!$item) {
                return 0.00;
            }
            $credited = (float) GstInvoiceItem::where('reference_invoice_item_id', $itemId)
                ->whereHas('invoice', fn($q) => $q->where('status', '!=', InvoiceStatus::CANCELLED->value))
                ->sum('total_amount');

            return max(0.00, round((float)$item->total_amount - $credited, 2));
        }

        $totalCredited = (float) GstInvoice::where('reference_invoice_id', $originalInvoice->id)
            ->where('invoice_type', InvoiceType::CREDIT_NOTE->value)
            ->where('status', '!=', InvoiceStatus::CANCELLED->value)
            ->sum('total');

        return max(0.00, round((float)$originalInvoice->total - $totalCredited, 2));
    }

    public function forceUpdate(
        GstInvoice $creditNote,
        mixed $recipient = null,
        ?array $items = null,
        ?InvoiceOptions $options = null,
        array $additionalAttributes = []
    ): GstInvoice {
        if ($creditNote->invoice_type !== InvoiceType::CREDIT_NOTE) {
            throw new InvalidGstInvoiceException("Document is not a Credit Note.");
        }
        return $this->service->forceUpdateInvoice($creditNote, $recipient, $items, $options, $additionalAttributes);
    }

    protected function validateAdjustableLimit(GstInvoice $originalInvoice, array $items): void
    {
        $remainingTotal = $this->getRemainingAdjustableAmount($originalInvoice);
        if ($remainingTotal <= 0) {
            throw new InvalidGstInvoiceException("Original invoice #{$originalInvoice->invoice_number} has already been fully credited.");
        }
    }
}
