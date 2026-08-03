<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case PARTIAL = 'partial';
    case OVERDUE = 'overdue';
}
