<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum RoundingStrategy: string
{
    case STANDARD = 'standard';
    case FLOOR = 'floor';
    case CEIL = 'ceil';
    case BANKERS = 'bankers';
}
