<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case APPLIED = 'applied';
    case CANCELLED = 'cancelled';

    /**
     * Get allowed statuses for a given InvoiceType.
     *
     * @return array<int, self>
     */
    public static function allowedForType(InvoiceType $type): array
    {
        return match ($type) {
            InvoiceType::TAX_INVOICE, InvoiceType::BILL_OF_SUPPLY => [
                self::DRAFT,
                self::ISSUED,
                self::CANCELLED,
            ],
            InvoiceType::CREDIT_NOTE, InvoiceType::DEBIT_NOTE, InvoiceType::RECEIPT_VOUCHER, InvoiceType::SIMPLE_RECEIPT => [
                self::DRAFT,
                self::ISSUED,
                self::APPLIED,
                self::CANCELLED,
            ],
            InvoiceType::QUOTATION => [
                self::DRAFT,
                self::SENT,
                self::ACCEPTED,
                self::REJECTED,
                self::EXPIRED,
                self::CANCELLED,
            ],
        };
    }

    public static function isAllowedForType(self|string $status, InvoiceType $type): bool
    {
        $statusValue = $status instanceof self ? $status : self::tryFrom($status);
        if (!$statusValue) {
            return false;
        }

        return in_array($statusValue, self::allowedForType($type), true);
    }
}
