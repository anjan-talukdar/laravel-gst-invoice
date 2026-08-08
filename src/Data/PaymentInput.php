<?php

namespace AnjanTalukdar\GstInvoice\Data;

use AnjanTalukdar\GstInvoice\Enums\PaymentMode;
use DateTimeInterface;
use JsonSerializable;

class PaymentInput implements JsonSerializable
{
    public ?string $paymentMode = null;

    public function __construct(
        public ?float $amount = null,
        public DateTimeInterface|string|null $paidAt = null,
        PaymentMode|string|null $paymentMode = null,
        public ?string $referenceNumber = null,
        public ?string $notes = null
    ) {
        $this->paymentMode = $paymentMode instanceof PaymentMode ? $paymentMode->value : ($paymentMode ? strtolower((string)$paymentMode) : null);
    }

    public static function make(
        ?float $amount = null,
        DateTimeInterface|string|null $paidAt = null,
        PaymentMode|string|null $paymentMode = null,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): self {
        return new self(
            amount: $amount,
            paidAt: $paidAt,
            paymentMode: $paymentMode,
            referenceNumber: $referenceNumber,
            notes: $notes
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: isset($data['amount']) ? (float)$data['amount'] : null,
            paidAt: $data['paid_at'] ?? ($data['paidAt'] ?? null),
            paymentMode: $data['payment_mode'] ?? ($data['paymentMode'] ?? null),
            referenceNumber: $data['reference_number'] ?? ($data['referenceNumber'] ?? null),
            notes: $data['notes'] ?? null
        );
    }

    public function amount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function paidAt(DateTimeInterface|string|null $paidAt): self
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function paymentMode(PaymentMode|string|null $paymentMode): self
    {
        $this->paymentMode = $paymentMode instanceof PaymentMode ? $paymentMode->value : ($paymentMode ? strtolower((string)$paymentMode) : null);
        return $this;
    }

    public function referenceNumber(?string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;
        return $this;
    }

    public function notes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'paid_at' => $this->paidAt,
            'payment_mode' => $this->paymentMode,
            'reference_number' => $this->referenceNumber,
            'notes' => $this->notes,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
