<?php

namespace AnjanTalukdar\GstInvoice\Data;

use JsonSerializable;

class InvoiceData implements JsonSerializable
{
    public string $schemaVersion = '1.0';

    public function __construct(
        public string $invoiceNumber,
        public string $invoiceDate,
        public ?string $dueDate,
        public string $paymentTerms,
        public string $paymentMode,
        public bool $isReverseCharge,
        public ?string $posStateName,
        public ?string $posStateCode,
        public bool $isInterstate,
        public PartySnapshotData $supplier,
        public PartySnapshotData $recipient,
        /** @var array<InvoiceItemData> */
        public array $items,
        public TaxSummaryData $summary,
        public array $gstSlabs,
        public string $amountInWords,
        public ?array $bankDetails = null,
        public string $currency = 'INR',
        public ?string $remark = null,
        public ?string $status = 'active',
        public ?string $paymentStatus = 'unpaid',
        public ?string $cancelledAt = null,
        public ?string $cancelledBy = null,
        public ?string $cancellationReason = null
    ) {}

    public static function fromArray(array $data): self
    {
        $itemsData = array_map(
            fn(array $item) => InvoiceItemData::fromArray($item),
            $data['items'] ?? []
        );

        return new self(
            invoiceNumber: $data['invoice_number'] ?? '',
            invoiceDate: $data['invoice_date'] ?? date('Y-m-d'),
            dueDate: $data['due_date'] ?? null,
            paymentTerms: $data['payment_terms'] ?? 'due_on_receipt',
            paymentMode: $data['payment_mode'] ?? 'bank_transfer',
            isReverseCharge: (bool)($data['is_reverse_charge'] ?? false),
            posStateName: $data['pos_state_name'] ?? null,
            posStateCode: $data['pos_state_code'] ?? null,
            isInterstate: (bool)($data['is_interstate'] ?? false),
            supplier: PartySnapshotData::fromArray($data['supplier'] ?? []),
            recipient: PartySnapshotData::fromArray($data['recipient'] ?? []),
            items: $itemsData,
            summary: TaxSummaryData::fromArray($data['summary'] ?? []),
            gstSlabs: $data['gst_slabs'] ?? [],
            amountInWords: $data['amount_in_words'] ?? '',
            bankDetails: $data['bank_details'] ?? null,
            currency: $data['currency'] ?? 'INR',
            remark: $data['remark'] ?? null,
            status: $data['status'] ?? 'active',
            paymentStatus: $data['payment_status'] ?? 'unpaid',
            cancelledAt: $data['cancelled_at'] ?? null,
            cancelledBy: $data['cancelled_by'] ?? null,
            cancellationReason: $data['cancellation_reason'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'payment_terms' => $this->paymentTerms,
            'payment_mode' => $this->paymentMode,
            'is_reverse_charge' => $this->isReverseCharge,
            'pos_state_name' => $this->posStateName,
            'pos_state_code' => $this->posStateCode,
            'is_interstate' => $this->isInterstate,
            'supplier' => $this->supplier->toArray(),
            'recipient' => $this->recipient->toArray(),
            'items' => array_map(fn(InvoiceItemData $item) => $item->toArray(), $this->items),
            'summary' => $this->summary->toArray(),
            'gst_slabs' => $this->gstSlabs,
            'amount_in_words' => $this->amountInWords,
            'bank_details' => $this->bankDetails,
            'currency' => $this->currency,
            'remark' => $this->remark,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'cancelled_at' => $this->cancelledAt,
            'cancelled_by' => $this->cancelledBy,
            'cancellation_reason' => $this->cancellationReason,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
