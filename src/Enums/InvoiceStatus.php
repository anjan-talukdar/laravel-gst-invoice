<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum InvoiceStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
}
