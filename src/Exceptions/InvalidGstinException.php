<?php

namespace AnjanTalukdar\GstInvoice\Exceptions;

use Exception;

class InvalidGstinException extends Exception
{
    public function __construct(string $gstin, string $reason = 'Invalid GSTIN format')
    {
        parent::__construct("{$reason}: '{$gstin}'");
    }
}
