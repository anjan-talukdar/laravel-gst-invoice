<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum InvoiceType: string
{
    case QUOTATION = 'quotation';
    case TAX_INVOICE = 'tax_invoice';
    case CREDIT_NOTE = 'credit_note';
    case DEBIT_NOTE = 'debit_note';
    case RECEIPT_VOUCHER = 'receipt_voucher';
    case BILL_OF_SUPPLY = 'bill_of_supply';
    case SIMPLE_RECEIPT = 'simple_receipt';

    public function label(): string
    {
        return match ($this) {
            self::QUOTATION => 'Quotation',
            self::TAX_INVOICE => 'Tax Invoice',
            self::CREDIT_NOTE => 'Credit Note',
            self::DEBIT_NOTE => 'Debit Note',
            self::RECEIPT_VOUCHER => 'Receipt Voucher',
            self::BILL_OF_SUPPLY => 'Bill of Supply',
            self::SIMPLE_RECEIPT => 'Simple Receipt',
        };
    }

    public function defaultPrefix(): string
    {
        $prefixes = config('gst-invoice.prefixes', []);

        return match ($this) {
            self::QUOTATION => $prefixes['quotation'] ?? 'QT',
            self::TAX_INVOICE => $prefixes['tax_invoice'] ?? 'INV',
            self::CREDIT_NOTE => $prefixes['credit_note'] ?? 'CN',
            self::DEBIT_NOTE => $prefixes['debit_note'] ?? 'DN',
            self::RECEIPT_VOUCHER => $prefixes['receipt_voucher'] ?? 'RV',
            self::BILL_OF_SUPPLY => $prefixes['bill_of_supply'] ?? 'BOS',
            self::SIMPLE_RECEIPT => $prefixes['simple_receipt'] ?? 'REC',
        };
    }
}
