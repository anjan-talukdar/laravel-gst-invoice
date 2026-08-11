<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceService;

class DebitNoteService
{
    public function __construct(protected GstInvoiceService $service) {}

    public function create(GstInvoice $originalInvoice, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        return $this->service->createDebitNote($originalInvoice, $items, $options);
    }

    public function createFromInvoice(GstInvoice $originalInvoice, ?InvoiceOptions $options = null): GstInvoice
    {
        $items = $originalInvoice->items->map(function ($item) {
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

    public function getRemainingAdjustableAmount(GstInvoice $originalInvoice): float
    {
        $totalDebited = (float) GstInvoice::where('reference_invoice_id', $originalInvoice->id)
            ->where('invoice_type', InvoiceType::DEBIT_NOTE->value)
            ->where('status', '!=', InvoiceStatus::CANCELLED->value)
            ->sum('total');

        return round((float)$originalInvoice->total + $totalDebited, 2);
    }

    public function forceUpdate(
        GstInvoice $debitNote,
        mixed $recipient = null,
        ?array $items = null,
        ?InvoiceOptions $options = null,
        array $additionalAttributes = []
    ): GstInvoice {
        if ($debitNote->invoice_type !== InvoiceType::DEBIT_NOTE) {
            throw new \AnjanTalukdar\GstInvoice\Exceptions\InvalidGstInvoiceException("Document is not a Debit Note.");
        }
        return $this->service->forceUpdateInvoice($debitNote, $recipient, $items, $options, $additionalAttributes);
    }
}
