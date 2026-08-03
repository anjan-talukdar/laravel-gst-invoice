<?php

namespace AnjanTalukdar\GstInvoice\Services;

use AnjanTalukdar\GstInvoice\Enums\IndianState;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstInvoiceException;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstinException;

class GstInvoiceValidator
{
    public const GSTIN_REGEX = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';

    /**
     * Validate a 15-character Indian GSTIN string.
     */
    public function validateGstin(?string $gstin, bool $strict = true): bool
    {
        if (empty($gstin)) {
            return true; // Null/empty GSTIN is allowed for unregistered recipients
        }

        $gstin = strtoupper(trim($gstin));

        if (strlen($gstin) !== 15) {
            if ($strict) {
                throw new InvalidGstinException($gstin, "GSTIN must be exactly 15 characters");
            }
            return false;
        }

        if ($strict && !preg_match(self::GSTIN_REGEX, $gstin)) {
            throw new InvalidGstinException($gstin, "GSTIN format invalid");
        }

        $stateCode = substr($gstin, 0, 2);
        if ($strict && !IndianState::fromCode($stateCode)) {
            throw new InvalidGstinException($gstin, "Invalid state code '{$stateCode}' in GSTIN");
        }

        return true;
    }

    /**
     * Extract 10-digit PAN from GSTIN.
     */
    public function extractPanFromGstin(?string $gstin): ?string
    {
        if (empty($gstin)) {
            return null;
        }

        $gstin = strtoupper(trim($gstin));
        if (strlen($gstin) === 15) {
            return substr($gstin, 2, 10);
        }

        return null;
    }

    /**
     * Validate full invoice generation input options and items array.
     */
    public function validateInvoiceInput(array $items, array $options = []): void
    {
        $errors = [];
        $config = config('gst-invoice.validation', []);

        if (empty($items)) {
            $errors[] = 'Invoice must contain at least one line item';
        }

        $maxItems = $config['max_items_per_invoice'] ?? 500;
        if (count($items) > $maxItems) {
            $errors[] = "Invoice line items count exceeds maximum allowed limit of {$maxItems}";
        }

        // Validate supplier if provided
        $supplier = $options['supplier'] ?? config('gst-invoice.supplier', []);
        $requireSupplierGstin = $config['require_supplier_gstin'] ?? false;
        if ($requireSupplierGstin && empty($supplier['gstin'])) {
            $errors[] = 'Supplier GSTIN is required by configuration';
        }

        if (!empty($supplier['gstin']) && ($config['validate_gstin_format'] ?? true)) {
            try {
                $this->validateGstin($supplier['gstin'], true);
            } catch (InvalidGstinException $e) {
                $errors[] = 'Supplier GSTIN error: ' . $e->getMessage();
            }
        }

        // Validate recipient GSTIN if provided
        $recipientGstin = $options['recipient_gstin'] ?? ($options['contact']['gstin'] ?? null);
        if (!empty($recipientGstin) && ($config['validate_gstin_format'] ?? true)) {
            try {
                $this->validateGstin($recipientGstin, true);
            } catch (InvalidGstinException $e) {
                $errors[] = 'Recipient GSTIN error: ' . $e->getMessage();
            }
        }

        // Validate items
        $allowedRates = $options['allowed_gst_rates'] ?? ($config['allowed_gst_rates'] ?? [0, 0.25, 3, 5, 12, 18, 28]);
        $validateHsnSac = $config['validate_hsn_sac_format'] ?? true;
        $allowZeroPrice = $config['allow_zero_price_items'] ?? true;

        foreach ($items as $index => $item) {
            $idx = $index + 1;
            if (empty($item['description'])) {
                $errors[] = "Item #{$idx} description is required";
            }

            $qty = (float)($item['quantity'] ?? 1);
            if ($qty <= 0) {
                $errors[] = "Item #{$idx} quantity must be greater than zero";
            }

            $price = (float)($item['unit_price'] ?? 0);
            if (!$allowZeroPrice && $price <= 0) {
                $errors[] = "Item #{$idx} unit price must be positive";
            }

            $rate = (float)($item['gst_rate'] ?? config('gst-invoice.default_gst_rate', 18));
            $allowedRatesFloat = array_map('floatval', $allowedRates);
            if (!empty($allowedRates) && !in_array($rate, $allowedRatesFloat, true)) {
                $errors[] = "Item #{$idx} GST rate {$rate}% is not in the list of allowed rates: " . implode(', ', $allowedRates);
            }

            if ($validateHsnSac && !empty($item['code'])) {
                $code = trim((string)$item['code']);
                $codeType = strtoupper($item['code_type'] ?? 'SAC');
                if ($codeType === 'HSN' && !preg_match('/^[0-9]{4,8}$/', $code)) {
                    $errors[] = "Item #{$idx} HSN code '{$code}' should be 4, 6, or 8 digits";
                } elseif ($codeType === 'SAC' && !preg_match('/^[0-9]{6}$/', $code)) {
                    $errors[] = "Item #{$idx} SAC code '{$code}' should be 6 digits";
                }
            }
        }

        if (!empty($errors)) {
            throw new InvalidGstInvoiceException('Invoice validation failed', $errors);
        }
    }
}
