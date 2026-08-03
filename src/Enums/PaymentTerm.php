<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum PaymentTerm: string
{
    case DUE_ON_RECEIPT = 'due_on_receipt';
    case NET_15 = 'net_15';
    case NET_30 = 'net_30';
    case NET_60 = 'net_60';
    case CUSTOM = 'custom';
}
