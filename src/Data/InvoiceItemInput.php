<?php

namespace AnjanTalukdar\GstInvoice\Data;

use AnjanTalukdar\GstInvoice\Enums\CodeType;
use AnjanTalukdar\GstInvoice\Enums\TaxCategory;
use JsonSerializable;

class InvoiceItemInput implements JsonSerializable
{
    public string $codeType = 'SAC';
    public string $taxCategory = 'taxable';
    public ?int $referenceInvoiceItemId = null;

    public function __construct(
        public string $description = '',
        public float $unitPrice = 0.0,
        public float $quantity = 1.0,
        public string $unit = 'Pcs',
        CodeType|string $codeType = CodeType::SAC,
        public string $code = '998313',
        TaxCategory|string $taxCategory = TaxCategory::TAXABLE,
        public float $gstRate = 18.0,
        public float $discount = 0.0,
        public int $sortOrder = 0,
        public ?array $metaData = null,
        ?int $referenceInvoiceItemId = null
    ) {
        $this->codeType = $codeType instanceof CodeType ? $codeType->value : strtoupper((string)$codeType);
        $this->taxCategory = $taxCategory instanceof TaxCategory ? $taxCategory->value : strtolower((string)$taxCategory);
        $this->referenceInvoiceItemId = $referenceInvoiceItemId;
    }

    public static function make(
        string $description = '',
        float $unitPrice = 0.0,
        float $quantity = 1.0,
        string $unit = 'Pcs',
        CodeType|string $codeType = CodeType::SAC,
        string $code = '998313',
        TaxCategory|string $taxCategory = TaxCategory::TAXABLE,
        float $gstRate = 18.0,
        float $discount = 0.0,
        int $sortOrder = 0,
        ?array $metaData = null,
        ?int $referenceInvoiceItemId = null
    ): self {
        return new self(
            description: $description,
            unitPrice: $unitPrice,
            quantity: $quantity,
            unit: $unit,
            codeType: $codeType,
            code: $code,
            taxCategory: $taxCategory,
            gstRate: $gstRate,
            discount: $discount,
            sortOrder: $sortOrder,
            metaData: $metaData,
            referenceInvoiceItemId: $referenceInvoiceItemId
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            description: (string)($data['description'] ?? ''),
            unitPrice: (float)($data['unit_price'] ?? ($data['unitPrice'] ?? 0.0)),
            quantity: (float)($data['quantity'] ?? 1.0),
            unit: (string)($data['unit'] ?? 'Pcs'),
            codeType: $data['code_type'] ?? ($data['codeType'] ?? CodeType::SAC),
            code: (string)($data['code'] ?? '998313'),
            taxCategory: $data['tax_category'] ?? ($data['taxCategory'] ?? TaxCategory::TAXABLE),
            gstRate: (float)($data['gst_rate'] ?? ($data['gstRate'] ?? 18.0)),
            discount: (float)($data['discount'] ?? ($data['item_discount'] ?? ($data['itemDiscount'] ?? 0.0))),
            sortOrder: (int)($data['sort_order'] ?? ($data['sortOrder'] ?? 0)),
            metaData: $data['meta_data'] ?? ($data['metaData'] ?? null),
            referenceInvoiceItemId: isset($data['reference_invoice_item_id']) ? (int)$data['reference_invoice_item_id'] : ($data['referenceInvoiceItemId'] ?? null)
        );
    }

    public function referenceInvoiceItemId(?int $id): self
    {
        $this->referenceInvoiceItemId = $id;
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function unitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function quantity(float $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function unit(string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function codeType(CodeType|string $codeType): self
    {
        $this->codeType = $codeType instanceof CodeType ? $codeType->value : strtoupper((string)$codeType);
        return $this;
    }

    public function code(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function taxCategory(TaxCategory|string $taxCategory): self
    {
        $this->taxCategory = $taxCategory instanceof TaxCategory ? $taxCategory->value : strtolower((string)$taxCategory);
        return $this;
    }

    public function gstRate(float $gstRate): self
    {
        $this->gstRate = $gstRate;
        return $this;
    }

    public function discount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function sortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function metaData(?array $metaData): self
    {
        $this->metaData = $metaData;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'code_type' => $this->codeType,
            'code' => $this->code,
            'tax_category' => $this->taxCategory,
            'gst_rate' => $this->gstRate,
            'discount' => $this->discount,
            'sort_order' => $this->sortOrder,
            'meta_data' => $this->metaData,
            'reference_invoice_item_id' => $this->referenceInvoiceItemId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
