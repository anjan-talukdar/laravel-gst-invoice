<?php

namespace AnjanTalukdar\GstInvoice\Exceptions;

use Exception;

class InvalidGstInvoiceException extends Exception
{
    protected array $errors = [];

    public function __construct(string $message = 'Invalid GST invoice data', array $errors = [], int $code = 0, ?Exception $previous = null)
    {
        $this->errors = $errors;
        if (!empty($errors)) {
            $message .= ': ' . implode('; ', $errors);
        }
        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
