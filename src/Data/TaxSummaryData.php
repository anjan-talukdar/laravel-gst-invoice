<?php

namespace AnjanTalukdar\GstInvoice\Data;

use JsonSerializable;

class TaxSummaryData implements JsonSerializable
{
    public function __construct(
        public float $grossTaxable,
        public float $discount,
        public float $subtotal, // Net taxable amount
        public float $cgstAmount,
        public float $sgstAmount,
        public float $igstAmount,
        public float $gstAmount,
        public float $roundOff,
        public float $total,
        public float $paidAmount = 0.00,
        public float $dueAmount = 0.00
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            grossTaxable: (float)($data['gross_taxable'] ?? 0),
            discount: (float)($data['discount'] ?? 0),
            subtotal: (float)($data['subtotal'] ?? ($data['net_taxable'] ?? 0)),
            cgstAmount: (float)($data['cgst_amount'] ?? ($data['cgst'] ?? 0)),
            sgstAmount: (float)($data['sgst_amount'] ?? ($data['sgst'] ?? 0)),
            igstAmount: (float)($data['igst_amount'] ?? ($data['igst'] ?? 0)),
            gstAmount: (float)($data['gst_amount'] ?? 0),
            roundOff: (float)($data['round_off'] ?? 0),
            total: (float)($data['total'] ?? ($data['grand_total'] ?? 0)),
            paidAmount: (float)($data['paid_amount'] ?? ($data['paid'] ?? 0)),
            dueAmount: (float)($data['due_amount'] ?? ($data['due'] ?? 0))
        );
    }

    public function toArray(): array
    {
        return [
            'gross_taxable' => $this->grossTaxable,
            'discount' => $this->discount,
            'subtotal' => $this->subtotal,
            'cgst_amount' => $this->cgstAmount,
            'sgst_amount' => $this->sgstAmount,
            'igst_amount' => $this->igstAmount,
            'gst_amount' => $this->gstAmount,
            'round_off' => $this->roundOff,
            'total' => $this->total,
            'paid_amount' => $this->paidAmount,
            'due_amount' => $this->dueAmount,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
