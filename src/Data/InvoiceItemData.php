<?php

namespace AnjanTalukdar\GstInvoice\Data;

use JsonSerializable;

class InvoiceItemData implements JsonSerializable
{
    public function __construct(
        public string $description,
        public string $codeType,        // HSN or SAC
        public string $code,            // Code string
        public string $taxCategory,     // taxable, exempt, nil_rated, non_gst
        public float $quantity,
        public string $unit,
        public float $unitPrice,
        public float $itemDiscount,
        public float $billDiscount,
        public float $taxableAmount,
        public float $gstRate,
        public float $cgstAmount,
        public float $sgstAmount,
        public float $igstAmount,
        public float $gstAmount,
        public float $totalAmount,
        public ?array $metaData = null,
        public int $sortOrder = 0
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'] ?? '',
            codeType: strtoupper($data['code_type'] ?? 'SAC'),
            code: (string)($data['code'] ?? '998313'),
            taxCategory: strtolower($data['tax_category'] ?? 'taxable'),
            quantity: (float)($data['quantity'] ?? 1),
            unit: $data['unit'] ?? 'Pcs',
            unitPrice: (float)($data['unit_price'] ?? 0),
            itemDiscount: (float)($data['item_discount'] ?? ($data['discount'] ?? 0)),
            billDiscount: (float)($data['bill_discount'] ?? ($data['allocated_bill_discount'] ?? 0)),
            taxableAmount: (float)($data['taxable_amount'] ?? ($data['taxable_value'] ?? 0)),
            gstRate: (float)($data['gst_rate'] ?? 0),
            cgstAmount: (float)($data['cgst_amount'] ?? 0),
            sgstAmount: (float)($data['sgst_amount'] ?? 0),
            igstAmount: (float)($data['igst_amount'] ?? 0),
            gstAmount: (float)($data['gst_amount'] ?? 0),
            totalAmount: (float)($data['total_amount'] ?? ($data['total'] ?? 0)),
            metaData: $data['meta_data'] ?? null,
            sortOrder: (int)($data['sort_order'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'code_type' => $this->codeType,
            'code' => $this->code,
            'tax_category' => $this->taxCategory,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unitPrice,
            'item_discount' => $this->itemDiscount,
            'bill_discount' => $this->billDiscount,
            'taxable_amount' => $this->taxableAmount,
            'gst_rate' => $this->gstRate,
            'cgst_amount' => $this->cgstAmount,
            'sgst_amount' => $this->sgstAmount,
            'igst_amount' => $this->igstAmount,
            'gst_amount' => $this->gstAmount,
            'total_amount' => $this->totalAmount,
            'sort_order' => $this->sortOrder,
            'meta_data' => $this->metaData,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
