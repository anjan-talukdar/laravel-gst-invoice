<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum PaymentMode: string
{
    case CASH = 'cash';
    case UPI = 'upi';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case CHEQUE = 'cheque';
    case NET_BANKING = 'net_banking';
    case OTHER = 'other';
}
