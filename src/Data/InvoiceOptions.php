<?php

namespace AnjanTalukdar\GstInvoice\Data;

use AnjanTalukdar\GstInvoice\Enums\DiscountMode;
use AnjanTalukdar\GstInvoice\Enums\GstMode;
use AnjanTalukdar\GstInvoice\Enums\IndianState;
use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Enums\OddPaisaWeightage;
use AnjanTalukdar\GstInvoice\Enums\PaymentMode;
use AnjanTalukdar\GstInvoice\Enums\PaymentStatus;
use AnjanTalukdar\GstInvoice\Enums\PaymentTerm;
use AnjanTalukdar\GstInvoice\Enums\RoundingStrategy;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

class InvoiceOptions implements JsonSerializable
{
    public ?string $gstMode = null;
    public ?string $discountMode = null;
    public ?string $supplierStateCode = null;
    public ?string $posStateCode = null;
    public ?string $roundingStrategy = null;
    public ?string $oddPaisaWeightage = null;
    public ?string $paymentTerms = null;
    public ?string $paymentMode = null;
    public ?string $invoiceType = null;
    public ?int $referenceInvoiceId = null;
    public ?string $status = null;
    public ?string $paymentStatus = null;

    public function __construct(
        GstMode|string|null $gstMode = null,
        public float $discount = 0.0,
        DiscountMode|string|null $discountMode = null,
        IndianState|string|null $supplierStateCode = null,
        IndianState|string|null $posStateCode = null,
        public ?string $posStateName = null,
        public ?bool $isInterstate = null,
        public bool $isReverseCharge = false,
        RoundingStrategy|string|null $roundingStrategy = null,
        OddPaisaWeightage|string|null $oddPaisaWeightage = null,
        public ?string $invoiceNumber = null,
        public DateTimeInterface|string|null $invoiceDate = null,
        public ?int $dueDays = null,
        public DateTimeInterface|string|null $dueDate = null,
        PaymentTerm|string|null $paymentTerms = null,
        PaymentMode|string|null $paymentMode = null,
        public ?string $currency = null,
        public ?string $remark = null,
        public int|string|null $createdBy = null,
        public ?Model $invoicable = null,
        public ?SupplierInput $supplier = null,
        public ?RecipientInput $recipient = null,
        InvoiceType|string|null $invoiceType = null,
        ?int $referenceInvoiceId = null,
        InvoiceStatus|string|null $status = null,
        PaymentStatus|string|null $paymentStatus = null
    ) {
        $this->gstMode = $gstMode instanceof GstMode ? $gstMode->value : ($gstMode ? strtolower((string)$gstMode) : null);
        $this->discountMode = $discountMode instanceof DiscountMode ? $discountMode->value : ($discountMode ? strtolower((string)$discountMode) : null);
        $this->supplierStateCode = $this->formatStateCode($supplierStateCode);
        $this->posStateCode = $this->formatStateCode($posStateCode);
        $this->roundingStrategy = $roundingStrategy instanceof RoundingStrategy ? $roundingStrategy->value : ($roundingStrategy ? strtolower((string)$roundingStrategy) : null);
        $this->oddPaisaWeightage = $oddPaisaWeightage instanceof OddPaisaWeightage ? $oddPaisaWeightage->value : ($oddPaisaWeightage ? strtolower((string)$oddPaisaWeightage) : null);
        $this->paymentTerms = $paymentTerms instanceof PaymentTerm ? $paymentTerms->value : ($paymentTerms ? strtolower((string)$paymentTerms) : null);
        $this->paymentMode = $paymentMode instanceof PaymentMode ? $paymentMode->value : ($paymentMode ? strtolower((string)$paymentMode) : null);
        $this->invoiceType = $invoiceType instanceof InvoiceType ? $invoiceType->value : ($invoiceType ? strtolower((string)$invoiceType) : null);
        $this->referenceInvoiceId = $referenceInvoiceId;
        $this->status = $status instanceof InvoiceStatus ? $status->value : ($status ? strtolower((string)$status) : null);
        $this->paymentStatus = $paymentStatus instanceof PaymentStatus ? $paymentStatus->value : ($paymentStatus ? strtolower((string)$paymentStatus) : null);
    }

    public static function make(
        GstMode|string|null $gstMode = null,
        float $discount = 0.0,
        DiscountMode|string|null $discountMode = null,
        IndianState|string|null $supplierStateCode = null,
        IndianState|string|null $posStateCode = null,
        ?string $posStateName = null,
        ?bool $isInterstate = null,
        bool $isReverseCharge = false,
        RoundingStrategy|string|null $roundingStrategy = null,
        OddPaisaWeightage|string|null $oddPaisaWeightage = null,
        ?string $invoiceNumber = null,
        DateTimeInterface|string|null $invoiceDate = null,
        ?int $dueDays = null,
        DateTimeInterface|string|null $dueDate = null,
        PaymentTerm|string|null $paymentTerms = null,
        PaymentMode|string|null $paymentMode = null,
        ?string $currency = null,
        ?string $remark = null,
        int|string|null $createdBy = null,
        ?Model $invoicable = null,
        ?SupplierInput $supplier = null,
        ?RecipientInput $recipient = null,
        InvoiceType|string|null $invoiceType = null,
        ?int $referenceInvoiceId = null,
        InvoiceStatus|string|null $status = null,
        PaymentStatus|string|null $paymentStatus = null
    ): self {
        return new self(
            gstMode: $gstMode,
            discount: $discount,
            discountMode: $discountMode,
            supplierStateCode: $supplierStateCode,
            posStateCode: $posStateCode,
            posStateName: $posStateName,
            isInterstate: $isInterstate,
            isReverseCharge: $isReverseCharge,
            roundingStrategy: $roundingStrategy,
            oddPaisaWeightage: $oddPaisaWeightage,
            invoiceNumber: $invoiceNumber,
            invoiceDate: $invoiceDate,
            dueDays: $dueDays,
            dueDate: $dueDate,
            paymentTerms: $paymentTerms,
            paymentMode: $paymentMode,
            currency: $currency,
            remark: $remark,
            createdBy: $createdBy,
            invoicable: $invoicable,
            supplier: $supplier,
            recipient: $recipient,
            invoiceType: $invoiceType,
            referenceInvoiceId: $referenceInvoiceId,
            status: $status,
            paymentStatus: $paymentStatus
        );
    }

    public static function fromArray(array $data): self
    {
        $supplier = isset($data['supplier']) && is_array($data['supplier'])
            ? SupplierInput::fromArray($data['supplier'])
            : ($data['supplier'] ?? null);

        $recipient = isset($data['recipient']) && is_array($data['recipient'])
            ? RecipientInput::fromArray($data['recipient'])
            : ($data['recipient'] ?? null);

        return new self(
            gstMode: $data['gst_mode'] ?? ($data['gstMode'] ?? null),
            discount: (float)($data['discount'] ?? 0.0),
            discountMode: $data['discount_mode'] ?? ($data['discountMode'] ?? null),
            supplierStateCode: $data['supplier_state_code'] ?? ($data['supplierStateCode'] ?? null),
            posStateCode: $data['pos_state_code'] ?? ($data['posStateCode'] ?? null),
            posStateName: $data['pos_state_name'] ?? ($data['posStateName'] ?? null),
            isInterstate: isset($data['is_interstate']) ? (bool)$data['is_interstate'] : (isset($data['isInterstate']) ? (bool)$data['isInterstate'] : null),
            isReverseCharge: (bool)($data['is_reverse_charge'] ?? ($data['isReverseCharge'] ?? false)),
            roundingStrategy: $data['rounding_strategy'] ?? ($data['roundingStrategy'] ?? null),
            oddPaisaWeightage: $data['odd_paisa_weightage'] ?? ($data['oddPaisaWeightage'] ?? null),
            invoiceNumber: $data['invoice_number'] ?? ($data['invoiceNumber'] ?? null),
            invoiceDate: $data['invoice_date'] ?? ($data['invoiceDate'] ?? null),
            dueDays: isset($data['due_days']) ? (int)$data['due_days'] : (isset($data['dueDays']) ? (int)$data['dueDays'] : null),
            dueDate: $data['due_date'] ?? ($data['dueDate'] ?? null),
            paymentTerms: $data['payment_terms'] ?? ($data['paymentTerms'] ?? null),
            paymentMode: $data['payment_mode'] ?? ($data['paymentMode'] ?? null),
            currency: $data['currency'] ?? null,
            remark: $data['remark'] ?? null,
            createdBy: $data['created_by'] ?? ($data['createdBy'] ?? null),
            invoicable: $data['invoicable'] ?? null,
            supplier: $supplier instanceof SupplierInput ? $supplier : null,
            recipient: $recipient instanceof RecipientInput ? $recipient : null,
            invoiceType: $data['invoice_type'] ?? ($data['invoiceType'] ?? null),
            referenceInvoiceId: isset($data['reference_invoice_id']) ? (int)$data['reference_invoice_id'] : ($data['referenceInvoiceId'] ?? null),
            status: $data['status'] ?? null,
            paymentStatus: $data['payment_status'] ?? ($data['paymentStatus'] ?? null)
        );
    }

    public function invoiceType(InvoiceType|string|null $invoiceType): self
    {
        $this->invoiceType = $invoiceType instanceof InvoiceType ? $invoiceType->value : ($invoiceType ? strtolower((string)$invoiceType) : null);
        return $this;
    }

    public function referenceInvoiceId(?int $id): self
    {
        $this->referenceInvoiceId = $id;
        return $this;
    }

    public function status(InvoiceStatus|string|null $status): self
    {
        $this->status = $status instanceof InvoiceStatus ? $status->value : ($status ? strtolower((string)$status) : null);
        return $this;
    }

    public function paymentStatus(PaymentStatus|string|null $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus instanceof PaymentStatus ? $paymentStatus->value : ($paymentStatus ? strtolower((string)$paymentStatus) : null);
        return $this;
    }

    public function gstMode(GstMode|string|null $gstMode): self
    {
        $this->gstMode = $gstMode instanceof GstMode ? $gstMode->value : ($gstMode ? strtolower((string)$gstMode) : null);
        return $this;
    }

    public function discount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function discountMode(DiscountMode|string|null $discountMode): self
    {
        $this->discountMode = $discountMode instanceof DiscountMode ? $discountMode->value : ($discountMode ? strtolower((string)$discountMode) : null);
        return $this;
    }

    public function supplierStateCode(IndianState|string|null $supplierStateCode): self
    {
        $this->supplierStateCode = $this->formatStateCode($supplierStateCode);
        return $this;
    }

    public function posStateCode(IndianState|string|null $posStateCode): self
    {
        $this->posStateCode = $this->formatStateCode($posStateCode);
        return $this;
    }

    public function posStateName(?string $posStateName): self
    {
        $this->posStateName = $posStateName;
        return $this;
    }

    public function isInterstate(?bool $isInterstate): self
    {
        $this->isInterstate = $isInterstate;
        return $this;
    }

    public function isReverseCharge(bool $isReverseCharge): self
    {
        $this->isReverseCharge = $isReverseCharge;
        return $this;
    }

    public function roundingStrategy(RoundingStrategy|string|null $roundingStrategy): self
    {
        $this->roundingStrategy = $roundingStrategy instanceof RoundingStrategy ? $roundingStrategy->value : ($roundingStrategy ? strtolower((string)$roundingStrategy) : null);
        return $this;
    }

    public function oddPaisaWeightage(OddPaisaWeightage|string|null $oddPaisaWeightage): self
    {
        $this->oddPaisaWeightage = $oddPaisaWeightage instanceof OddPaisaWeightage ? $oddPaisaWeightage->value : ($oddPaisaWeightage ? strtolower((string)$oddPaisaWeightage) : null);
        return $this;
    }

    public function invoiceNumber(?string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    public function invoiceDate(DateTimeInterface|string|null $invoiceDate): self
    {
        $this->invoiceDate = $invoiceDate;
        return $this;
    }

    public function dueDays(?int $dueDays): self
    {
        $this->dueDays = $dueDays;
        return $this;
    }

    public function dueDate(DateTimeInterface|string|null $dueDate): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function paymentTerms(PaymentTerm|string|null $paymentTerms): self
    {
        $this->paymentTerms = $paymentTerms instanceof PaymentTerm ? $paymentTerms->value : ($paymentTerms ? strtolower((string)$paymentTerms) : null);
        return $this;
    }

    public function paymentMode(PaymentMode|string|null $paymentMode): self
    {
        $this->paymentMode = $paymentMode instanceof PaymentMode ? $paymentMode->value : ($paymentMode ? strtolower((string)$paymentMode) : null);
        return $this;
    }

    public function currency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function remark(?string $remark): self
    {
        $this->remark = $remark;
        return $this;
    }

    public function createdBy(int|string|null $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function invoicable(?Model $invoicable): self
    {
        $this->invoicable = $invoicable;
        return $this;
    }

    public function supplier(?SupplierInput $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function recipient(?RecipientInput $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    protected function formatStateCode(IndianState|string|null $stateCode): ?string
    {
        if ($stateCode instanceof IndianState) {
            return $stateCode->value;
        }

        if ($stateCode !== null && $stateCode !== '') {
            return str_pad((string)$stateCode, 2, '0', STR_PAD_LEFT);
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'invoice_type' => $this->invoiceType,
            'reference_invoice_id' => $this->referenceInvoiceId,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'gst_mode' => $this->gstMode,
            'discount' => $this->discount,
            'discount_mode' => $this->discountMode,
            'supplier_state_code' => $this->supplierStateCode,
            'pos_state_code' => $this->posStateCode,
            'pos_state_name' => $this->posStateName,
            'is_interstate' => $this->isInterstate,
            'is_reverse_charge' => $this->isReverseCharge,
            'rounding_strategy' => $this->roundingStrategy,
            'odd_paisa_weightage' => $this->oddPaisaWeightage,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'due_days' => $this->dueDays,
            'due_date' => $this->dueDate,
            'payment_terms' => $this->paymentTerms,
            'payment_mode' => $this->paymentMode,
            'currency' => $this->currency,
            'remark' => $this->remark,
            'created_by' => $this->createdBy,
            'invoicable' => $this->invoicable,
            'supplier' => $this->supplier?->toArray(),
            'recipient' => $this->recipient?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
