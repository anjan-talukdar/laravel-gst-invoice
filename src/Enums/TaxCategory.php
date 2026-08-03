<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum TaxCategory: string
{
    case TAXABLE = 'taxable';
    case EXEMPT = 'exempt';
    case NIL_RATED = 'nil_rated';
    case NON_GST = 'non_gst';
}
