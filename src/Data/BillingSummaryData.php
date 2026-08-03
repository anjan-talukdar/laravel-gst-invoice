<?php

namespace AnjanTalukdar\GstInvoice\Data;

use JsonSerializable;

class BillingSummaryData implements JsonSerializable
{
    public function __construct(
        public bool $isInterstate,
        public bool $isReverseCharge,
        public ?string $posStateName,
        public ?string $posStateCode,
        public string $discountMode,
        public TaxSummaryData $summary,
        /** @var array<InvoiceItemData> */
        public array $items,
        public array $gstSlabs,
        public array $auditTrail = []
    ) {}

    public function toArray(): array
    {
        return [
            'is_interstate' => $this->isInterstate,
            'is_reverse_charge' => $this->isReverseCharge,
            'pos_state_name' => $this->posStateName,
            'pos_state_code' => $this->posStateCode,
            'discount_mode' => $this->discountMode,
            'summary' => $this->summary->toArray(),
            'items' => array_map(fn(InvoiceItemData $item) => $item->toArray(), $this->items),
            'gst_slabs' => $this->gstSlabs,
            'audit_trail' => $this->auditTrail,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
